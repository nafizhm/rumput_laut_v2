<?php

namespace App\Services\Setting;

use App\Models\DeliveryNote;
use App\Models\DocumentNumberRule;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentNumberService
{
    public static function format(DocumentNumberRule $rule, Carbon $date, int $sequence): string
    {
        return strtr($rule->format, [
            '{SEQ}' => str_pad((string) $sequence, $rule->padding, '0', STR_PAD_LEFT),
            '{YYYY}' => $date->format('Y'), '{MM}' => $date->format('m'),
        ]);
    }

    public function next(string $type, string $date): string
    {
        return DB::transaction(function () use ($type, $date) {
            $rule = DocumentNumberRule::where('document_type', $type)->lockForUpdate()->firstOrFail();
            $date = Carbon::parse($date);
            $period = match ($rule->reset_period) {
                'month' => $date->format('Y-m'), 'year' => $date->format('Y'), default => 'all',
            };
            $counter = DB::table('document_number_counters')->where('document_number_rule_id', $rule->id)->where('period', $period)->first();
            $sequence = max((int) $rule->start_number, (int) ($counter?->next_number ?? 1));
            $model = $type === 'invoice' ? Invoice::class : DeliveryNote::class;
            do {
                $number = static::format($rule, $date, $sequence++);
            } while ($model::where('number', $number)->exists());
            if (strlen($number) > 100) {
                throw ValidationException::withMessages(['number' => 'Nomor dokumen melebihi 100 karakter. Sesuaikan format pada Profile.']);
            }
            DB::table('document_number_counters')->updateOrInsert(
                ['document_number_rule_id' => $rule->id, 'period' => $period], ['next_number' => $sequence]
            );
            return $number;
        });
    }
}
