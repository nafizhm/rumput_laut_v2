<?php

namespace App\Services\Finance;

use App\Models\AccountingSetting;
use App\Models\ChartOfAccount;
use App\Models\Inventory;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class JournalService
{
    public function startZero(string $date, User $user): JournalEntry
    {
        return DB::transaction(function () use ($date, $user) {
            $setting = AccountingSetting::lockForUpdate()->findOrFail(1);
            if ($setting->start_date || JournalEntry::where('kind', 'opening')->exists() || JournalEntry::where('status', 'posted')->exists()) {
                $this->fail('Pembukuan sudah dimulai atau draft saldo awal sudah tersedia.');
            }
            $this->checkDate($date, $setting, true);
            $first = JournalEntry::where('is_automatic', true)->where('status', 'draft')->min('entry_date');
            if ($first && $date > $first) {
                $this->fail('Tanggal mulai tidak boleh setelah transaksi operasional pertama.');
            }
            if (Inventory::where('stock_stage', 'legacy')->where('current_stock_kg', '<>', 0)->exists()) {
                $this->fail('Stok lama harus diverifikasi dalam Saldo Awal, bukan mulai dengan saldo nol.');
            }
            $entry = JournalEntry::create(['entry_date' => $date, 'kind' => 'opening', 'status' => 'posted', 'description' => 'Mulai pembukuan dengan saldo awal nol yang dikonfirmasi',
                'created_by' => $user->id, 'created_by_name' => $user->name, 'posted_by' => $user->id, 'posted_by_name' => $user->name, 'posted_at' => now()]);
            $entry->number = 'SA-'.str_replace('-', '', $date).'-'.str_pad($entry->id, 6, '0', STR_PAD_LEFT);
            $entry->save();
            $setting->start_date = $date;
            $setting->save();
            app(OperationalAccountingService::class)->postPending();

            return $entry;
        });
    }

    public function save(array $data, User $user, ?JournalEntry $entry = null): JournalEntry
    {
        return DB::transaction(function () use ($data, $user, $entry) {
            $setting = AccountingSetting::lockForUpdate()->findOrFail(1);
            if ($entry) {
                $entry = JournalEntry::lockForUpdate()->findOrFail($entry->id);
                if ($entry->is_automatic) {
                    $this->fail('Jurnal otomatis dikoreksi melalui transaksi sumber, bukan diubah manual.');
                }
                $this->assertDraft($entry);
                $data['kind'] = $entry->kind;
            }
            if (! $entry && $data['kind'] === 'opening' && $setting->start_date) {
                $this->fail('Saldo awal sudah diposting. Koreksi melalui jurnal penyesuaian.');
            }
            if (str_starts_with($data['source_reference'] ?? '', 'op:')) {
                $this->fail('Referensi op: digunakan khusus jurnal otomatis.');
            }
            $this->checkDate($data['entry_date'], $setting, false);
            $lines = $this->prepareLines($data['lines'], $data['kind']);
            unset($data['lines']);
            if (! $entry) {
                $entry = JournalEntry::create($data + ['created_by' => $user->id, 'created_by_name' => $user->name]);
                $entry->number = ($data['kind'] === 'opening' ? 'SA' : 'JU').'-'.str_replace('-', '', $data['entry_date']).'-'.str_pad($entry->id, 6, '0', STR_PAD_LEFT);
                $entry->save();
            } else {
                $entry->update($data);
                $entry->lines()->delete();
            }
            $entry->lines()->createMany($lines);

            return $entry;
        }, 3);
    }

    public function post(JournalEntry $entry, User $user): JournalEntry
    {
        return DB::transaction(function () use ($entry, $user) {
            // Serialize posting and opening cut-off to avoid races.
            $setting = AccountingSetting::lockForUpdate()->findOrFail(1);
            $entry = JournalEntry::lockForUpdate()->findOrFail($entry->id);
            if ($entry->is_automatic) {
                $this->fail('Jurnal otomatis diposting oleh sistem setelah saldo awal, dan dikoreksi melalui transaksi sumber.');
            }
            $this->assertDraft($entry);
            $this->checkDate($entry->entry_date->format('Y-m-d'), $setting, true);
            if ($entry->kind === 'opening') {
                if (Schema::hasTable('operational_events')) {
                    $first = JournalEntry::where('is_automatic', true)->where('status', 'draft')->min('entry_date');
                    if ($first && $entry->entry_date->format('Y-m-d') > $first) {
                        $this->fail('Tanggal mulai pembukuan tidak boleh setelah transaksi operasional pertama.');
                    }
                }
                if ($setting->start_date || JournalEntry::where('status', 'posted')->exists()) {
                    $this->fail('Saldo awal hanya boleh diposting satu kali sebelum jurnal lainnya.');
                }
            } elseif (! $setting->start_date) {
                $this->fail('Isi dan posting Saldo Awal terlebih dahulu untuk menentukan tanggal mulai pembukuan.');
            }
            $input = $entry->lines()->get()->map(fn ($line) => [
                'chart_of_account_id' => $line->chart_of_account_id,
                'debit' => AccountingMoney::decimal($line->debit_cents),
                'credit' => AccountingMoney::decimal($line->credit_cents),
            ])->all();
            $lines = $this->prepareLines($input, $entry->kind);
            $debit = array_sum(array_column($lines, 'debit_cents'));
            $credit = array_sum(array_column($lines, 'credit_cents'));
            if ($debit !== $credit || $debit <= 0) {
                $this->fail('Posting ditolak: total debit dan kredit harus sama dan lebih dari nol.');
            }
            // Snapshot COA at posting. Later master edits cannot rewrite history.
            $entry->lines()->delete();
            $entry->lines()->createMany($lines);
            $entry->status = 'posted';
            $entry->posted_at = now();
            $entry->posted_by = $user->id;
            $entry->posted_by_name = $user->name;
            $entry->save();
            if ($entry->kind === 'opening') {
                $setting->start_date = $entry->entry_date;
                $setting->save();
                app(OperationalAccountingService::class)->postPending();
            }

            return $entry;
        }, 3);
    }

    public function delete(JournalEntry $entry): void
    {
        DB::transaction(function () use ($entry) {
            $entry = JournalEntry::lockForUpdate()->findOrFail($entry->id);
            if ($entry->is_automatic) {
                $this->fail('Jurnal otomatis tidak dapat dihapus. Koreksi transaksi sumber.');
            }
            $this->assertDraft($entry);
            $entry->delete();
        });
    }

    public function reverse(JournalEntry $entry, string $date, string $reason, User $user): JournalEntry
    {
        return DB::transaction(function () use ($entry, $date, $reason, $user) {
            $setting = AccountingSetting::lockForUpdate()->findOrFail(1);
            $entry = JournalEntry::lockForUpdate()->findOrFail($entry->id);
            if ($entry->is_automatic) {
                $this->fail('Pembalik jurnal otomatis dibuat melalui koreksi transaksi sumber.');
            }
            if ($entry->status !== 'posted' || $entry->kind !== 'general' || $entry->reversal()->exists()) {
                $this->fail('Hanya jurnal umum Posted yang belum dibalik yang dapat dibuatkan pembalik. Saldo awal dikoreksi lewat jurnal penyesuaian.');
            }
            $this->checkDate($date, $setting, true);
            if (strtotime($date) < strtotime($entry->entry_date->format('Y-m-d'))) {
                $this->fail('Tanggal pembalik tidak boleh sebelum jurnal asal.');
            }
            $reversal = JournalEntry::create([
                'entry_date' => $date, 'kind' => 'reversal', 'status' => 'posted', 'reversal_of_id' => $entry->id,
                'description' => 'Pembalik '.$entry->number.': '.$reason,
                'created_by' => $user->id, 'created_by_name' => $user->name,
                'posted_by' => $user->id, 'posted_by_name' => $user->name, 'posted_at' => now(),
            ]);
            $reversal->number = 'REV-'.str_replace('-', '', $date).'-'.str_pad($reversal->id, 6, '0', STR_PAD_LEFT);
            $reversal->save();
            foreach ($entry->lines as $line) {
                $attributes = $line->only(['chart_of_account_id', 'account_code', 'account_name', 'category', 'account_group', 'normal_balance']);
                $reversal->lines()->create($attributes + ['debit_cents' => $line->credit_cents, 'credit_cents' => $line->debit_cents]);
            }

            return $reversal;
        }, 3);
    }

    private function prepareLines(array $input, string $kind): array
    {
        if (count($input) < 2 || count($input) > 100) {
            $this->fail('Jurnal harus memiliki 2–100 baris.');
        }
        $result = [];
        foreach ($input as $index => $row) {
            $account = ChartOfAccount::lockForUpdate()->findOrFail($row['chart_of_account_id']);
            if (! $account->is_active) {
                $this->fail('Akun '.$account->code.' nonaktif.');
            }
            if (! array_key_exists($account->category, ChartOfAccount::CATEGORIES)) {
                $this->fail('Kategori akun '.$account->code.' tidak valid.');
            }
            if ($account->code === '32102') {
                $this->fail('Laba/Rugi Tahun Berjalan dihitung otomatis oleh laporan. Gunakan Laba Ditahan untuk saldo laba awal.');
            }
            if ($kind === 'opening' && ! in_array($account->category, ['Harta', 'Utang', 'Modal'])) {
                $this->fail('Saldo awal hanya menggunakan akun Harta, Utang, dan Modal.');
            }
            $debit = AccountingMoney::cents($row['debit'] ?? '0');
            $credit = AccountingMoney::cents($row['credit'] ?? '0');
            if (($debit > 0) === ($credit > 0)) {
                throw ValidationException::withMessages(['lines.'.$index.'.debit' => 'Setiap baris harus berisi salah satu Debit atau Kredit yang lebih dari nol.']);
            }
            $result[] = [
                'chart_of_account_id' => $account->id, 'debit_cents' => $debit, 'credit_cents' => $credit,
                'account_code' => $account->code, 'account_name' => $account->name, 'category' => $account->category,
                'account_group' => $account->account_group, 'normal_balance' => $account->normal_balance,
            ];
        }

        return $result;
    }

    private function checkDate(string $date, AccountingSetting $setting, bool $posting): void
    {
        $day = Carbon::parse($date)->startOfDay();
        if ($setting->start_date && $day->lt($setting->start_date)) {
            $this->fail('Tanggal jurnal tidak boleh sebelum tanggal mulai pembukuan.');
        }
        if ($posting && $day->gt(Carbon::now('Asia/Jakarta')->startOfDay())) {
            $this->fail('Jurnal bertanggal masa depan tidak dapat diposting.');
        }
    }

    private function assertDraft(JournalEntry $entry): void
    {
        if ($entry->status !== 'draft' || $entry->posted_at) {
            $this->fail('Jurnal Posted dikunci. Gunakan pembalik atau jurnal penyesuaian untuk koreksi.');
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['journal' => $message]);
    }
}
