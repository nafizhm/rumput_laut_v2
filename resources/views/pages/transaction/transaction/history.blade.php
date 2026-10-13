@extends('layouts.app')
@section('title', 'Riwayat Pemasukan & Pengeluaran')
@section('content')
<div class="space-y-5">
    <h1 class="text-xl font-semibold">Riwayat Pemasukan & Pengeluaran {{ $id ? '#'.$id : '' }}</h1>
    <p class="text-sm text-gray-500">Riwayat tetap tersedia setelah transaksi dibatalkan. Riwayat lengkap mulai direkam sejak fitur ini diaktifkan; jurnal lama ditampilkan sebagai referensi tanpa menebak alasan perubahan.</p>
    <a href="{{ route('manajemen-pemasukan-pengeluaran.index') }}" class="text-brand-500">Kembali ke transaksi</a>
    <form method="get" class="flex gap-3">
        <input type="number" min="1" name="transaction_id" value="{{ $id }}" placeholder="ID transaksi" class="rounded-lg border p-2">
        <button class="rounded-lg bg-brand-500 px-4 py-2 text-white">Cari</button>
        <a href="{{ route('manajemen-pemasukan-pengeluaran.history') }}" class="p-2 text-brand-500">Semua riwayat</a>
    </form>
    @forelse($histories as $history)
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:bg-gray-900 dark:border-gray-800">
            <p class="font-semibold">{{ ['created'=>'Dicatat', 'updated'=>'Diubah', 'deleted'=>'Dibatalkan'][$history->action] }} ·
                <a class="text-brand-500" href="{{ route('manajemen-pemasukan-pengeluaran.history', ['transaction_id'=>$history->transaction_id]) }}">Transaksi #{{ $history->transaction_id }}</a>
            </p>
            <p class="text-sm text-gray-500">{{ $history->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB · {{ $history->actor_name }}</p>
            <p class="mt-2">Alasan: {{ $history->reason ?? 'Pencatatan awal' }}</p>
            <div class="overflow-x-auto mt-3"><table class="w-full text-sm text-left">
                <thead><tr><th>Data</th><th>Sebelum</th><th>Sesudah</th></tr></thead>
                <tbody>
                @foreach(['type'=>'Jenis', 'date'=>'Tanggal', 'amount'=>'Nominal', 'description'=>'Keterangan', 'notes'=>'Catatan', 'account_name'=>'Akun transaksi', 'cash_account_name'=>'Kas / Bank'] as $field=>$label)
                    <tr class="border-t"><td class="py-2">{{ $label }}</td>
                    @foreach([$history->before, $history->after] as $snapshot)
                        @php($value = $snapshot[$field] ?? null)
                        <td>{{ $value === null ? '—' : ($field === 'amount' ? 'Rp '.number_format($value, 0, ',', '.') : ($field === 'type' ? ($value === 'income' ? 'Pemasukan' : 'Pengeluaran') : ($field === 'date' ? \Carbon\Carbon::parse($value)->format('d/m/Y') : $value))) }}</td>
                    @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    @empty
        <p>Belum ada riwayat perubahan yang direkam.</p>
    @endforelse
    {{ $histories->links() }}
    @if($journals->isNotEmpty())
        <h2 class="font-semibold">Jejak jurnal transaksi</h2>
        @foreach($journals as $journal)
            <div class="rounded-lg border p-4">
                <p>{{ $journal->entry_date->format('d/m/Y') }} · {{ $journal->number }} · {{ $journal->status }}</p>
                <p>{{ $journal->description }}</p>
                <p class="text-sm text-gray-500">Dicatat oleh {{ $journal->created_by_name ?? 'Tidak tersedia' }} · {{ $journal->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }} WIB</p>
                @php($cash = $journal->lines->where('category', 'Harta')->where('account_group', 'Kas dan Bank'))
                <p>Perubahan kas: Rp {{ number_format(($cash->sum('debit_cents') - $cash->sum('credit_cents')) / 100, 0, ',', '.') }}</p>
            </div>
        @endforeach
    @endif
</div>
@endsection
