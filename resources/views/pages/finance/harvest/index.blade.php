@extends('layouts.app')
@section('title', 'Pembelian & Pembayaran Panen')
@section('breadcrumb')<x-breadcrumb pageTitle="Pembelian & Pembayaran Panen" :links="[['name'=>'Keuangan','url'=>route('harvest-finance.index')]]" />@endsection
@section('content')
<div class="space-y-5 rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
    <x-info-help><p class="text-sm text-gray-500">Tetapkan harga panen yang sudah diterima, lalu catat pembayaran dan potongan pinjaman. Pembayaran dapat dilakukan bertahap.</p></x-info-help>
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <label for="status" class="text-sm">Status pembayaran</label>
        <select id="status" name="status" class="rounded-lg border border-gray-300 bg-transparent p-2 dark:border-gray-700">
            @foreach([''=>'Semua', 'waiting'=>'Menunggu Penetapan Harga', 'unpaid'=>'Belum Terbayar', 'partial'=>'Terbayar Sebagian', 'paid'=>'Sudah Terbayar', 'legacy'=>'Riwayat Lama'] as $value=>$label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach
        </select><button class="rounded-lg bg-brand-500 px-4 py-2 text-white">Tampilkan</button>
    </form>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead><tr>@foreach(['Panen / Petani', 'Tanggal Terima', 'Berat Referensi', 'Nilai Pembelian', 'Dibayar', 'Potongan Pinjaman', 'Sisa Utang Pembelian', 'Status', 'Aksi'] as $label)<th class="whitespace-nowrap p-3">{{ $label }}</th>@endforeach</tr></thead>
        <tbody>@forelse($harvests as $harvest)<tr class="border-t border-gray-200 dark:border-gray-800">
            <td class="p-3"><strong>{{ $harvest->batch_code }}</strong><br>{{ $harvest->farmer->name }}</td>
            <td class="p-3">{{ $harvest->harvest_date->format('d/m/Y') }}</td>
            <td class="whitespace-nowrap p-3">{{ number_format($harvest->total_weight,2,',','.') }} kg</td>
            <td class="whitespace-nowrap p-3">{{ $harvest->separate_payment && !$harvest->priced_at ? '-' : 'Rp '.number_format($harvest->total_harvest_value,0,',','.') }}</td>
            <td class="whitespace-nowrap p-3">Rp {{ number_format($harvest->cash_payout,0,',','.') }}</td>
            <td class="whitespace-nowrap p-3">Rp {{ number_format($harvest->debt_deduction,0,',','.') }}</td>
            <td class="whitespace-nowrap p-3">{{ $harvest->separate_payment && !$harvest->priced_at ? '-' : 'Rp '.number_format($harvest->outstanding_payable,0,',','.') }}</td>
            @php
                $statusClass = match ($harvest->payment_status) {
                    'Menunggu Penetapan Harga' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                    'Belum Terbayar' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                    'Terbayar Sebagian' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                    'Sudah Terbayar' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200',
                };
            @endphp
            <td class="p-3"><span class="inline-flex whitespace-nowrap rounded-lg px-2.5 py-1 text-xs font-medium {{ $statusClass }}">{{ $harvest->payment_status }}</span></td>
            <td class="p-3"><a @class([
                'inline-flex items-center justify-center whitespace-nowrap rounded-lg px-3 py-2 text-xs font-semibold text-white transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
                'bg-blue-600 hover:bg-blue-700 focus-visible:outline-blue-600' => $harvest->separate_payment,
                'bg-violet-600 hover:bg-violet-700 focus-visible:outline-violet-600' => !$harvest->separate_payment,
            ]) href="{{ route('harvest-finance.show',$harvest) }}">{{ $harvest->separate_payment ? 'Proses / Lihat' : 'Lihat Riwayat' }}</a></td>
        </tr>@empty<tr><td colspan="9" class="p-5 text-center text-gray-500">Belum ada panen untuk status ini.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $harvests->links() }}
</div>
@endsection
