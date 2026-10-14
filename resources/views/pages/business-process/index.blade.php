@extends('layouts.app')
@push('styles')<style>[data-business-card]{color:#1f2937!important;background:#fff!important}html.dark [data-business-card]{color:#f3f4f6!important;background:#111827!important} [data-business-muted]{color:#4b5563!important}html.dark [data-business-muted]{color:#d1d5db!important}</style>@endpush
@section('title', 'Proses Bisnis')
@section('breadcrumb')<x-breadcrumb pageTitle="Proses Bisnis" :links="[]" />@endsection

@push('styles')
<style>
.business-month{background:#fff;color:#182230;color-scheme:light;border:1px solid #98a2b3}
html.dark .business-month{background:#182230;color:#f9fafb;color-scheme:dark}
[data-business-card] .text-gray-600,[data-business-card] .text-gray-500{color:#475467!important}
html.dark [data-business-card] .text-gray-600,html.dark [data-business-card] .text-gray-500{color:#d0d5dd!important}
[data-business-card] .tabular-nums{overflow-wrap:anywhere;white-space:normal}
.bg-gradient-to-br.from-brand-700{background:linear-gradient(125deg,#064e3b,#047857)!important;color:#fff}

.business-chart{display:flex;align-items:flex-end;gap:12px;min-width:600px}
.business-chart-month{flex:1 1 0;min-width:0}
.business-chart-bars{display:flex;align-items:flex-end;gap:4px;height:160px}
.business-chart-bar{display:block;flex:1 1 0;min-width:8px;border-radius:4px 4px 0 0}
.business-submit{background:#fff;color:#065f46}
.business-dashboard .business-info{background:#eff6ff!important;color:#1e3a8a!important;border-color:#bfdbfe!important}
html.dark .business-dashboard .business-info{background:#172d48!important;color:#dbeafe!important;border-color:#385777!important}
.business-dashboard .business-trend{color:#1f2937}
.business-dashboard .business-trend th,.business-dashboard .business-trend td{border-bottom:1px solid #d0d5dd}
.business-dashboard .business-trend thead{background:#f2f4f7}
html.dark .business-dashboard .business-trend{color:#f3f4f6}
html.dark .business-dashboard .business-trend thead{background:#253247}
html.dark .business-dashboard .business-trend th,html.dark .business-dashboard .business-trend td{border-color:#475467}
html.dark .business-dashboard [data-business-card]{border-color:#475467}
.business-dashboard select:focus-visible,.business-dashboard button:focus-visible,.business-dashboard [tabindex]:focus-visible{outline:3px solid #10b981;outline-offset:3px}
</style>
@endpush
@section('content')
@php
    $money = \App\Services\Finance\AccountingMoney::class;
    $assets = collect($report['assets'] ?? []);
    $cash = $assets->where('group', 'Kas dan Bank')->sum('amount');
    $farmerReceivable = $assets->where('code', '11201')->sum('amount');
    $receivable = $assets->where('group', 'Piutang')->where('code', '!=', '11201')->sum('amount');
    $inventory = $assets->where('group', 'Persediaan')->sum('amount');
    $fixed = $assets->whereIn('group', ['Aset Tetap', 'Akumulasi Penyusutan'])->sum('amount');
    $otherAssets = $assets->sum('amount') - $cash - $farmerReceivable - $receivable - $inventory - $fixed;
    $profit = $report['net_profit'] ?? 0;
    $duration = $report['start_date'] ? $report['start_date']->diffForHumans(now('Asia/Jakarta'), true) : 'Belum ditentukan';
    $maxTrend = max(1, $trend->max(fn ($item) => max(abs($item['sales']), abs($item['expense']))));
@endphp
<div class="business-dashboard space-y-5 text-gray-800 dark:text-gray-100">
    <div class="flex flex-col gap-4 rounded-2xl bg-gradient-to-br from-brand-700 to-brand-500 p-5 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between sm:p-7">
        <div><p class="text-sm font-medium text-white/75">Ringkasan kesehatan usaha</p><h1 class="mt-1 text-2xl font-bold sm:text-3xl">Proses Bisnis</h1><p class="mt-2 text-sm text-white/80">Berjalan {{ $duration }}{{ $report['start_date'] ? ' · sejak '.$report['start_date']->locale('id')->translatedFormat('d F Y') : '' }}</p></div>
        <form method="GET" class="w-full space-y-3 sm:w-auto">
            <div class="grid grid-cols-2 gap-2">
                <div><label for="business-month" class="mb-1 block text-sm font-semibold">Bulan</label><select id="business-month" name="month_number" class="business-month min-h-11 w-full rounded-lg px-3 text-sm">@foreach(range(1, 12) as $monthNumber)<option value="{{ $monthNumber }}" @selected((int) substr($month, 5, 2) === $monthNumber)>{{ \Illuminate\Support\Carbon::create(2000, $monthNumber, 1)->locale('id')->translatedFormat('F') }}</option>@endforeach</select></div>
                <div><label for="business-year" class="mb-1 block text-sm font-semibold">Tahun</label><select id="business-year" name="year" class="business-month min-h-11 w-full rounded-lg px-3 text-sm">@foreach(range(max(now('Asia/Jakarta')->year + 1, (int) substr($month, 0, 4)), min($report['start_date']?->year ?? now('Asia/Jakarta')->year, (int) substr($month, 0, 4))) as $year)<option value="{{ $year }}" @selected((int) substr($month, 0, 4) === $year)>{{ $year }}</option>@endforeach</select></div>
            </div>
            <button class="business-submit min-h-11 w-full rounded-lg px-4 text-sm font-semibold">Tampilkan laporan</button>
            <a class="block py-2 text-center text-sm underline" href="{{ route('proses-bisnis.index') }}">Kembali ke bulan ini</a>
        </form>
    </div>
    @if(!$report['ready'])
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-900 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-200">Laporan belum siap dibaca. Tentukan tanggal mulai pembukuan dan posting saldo awal terlebih dahulu.</div>
    @else
        <section class="rounded-2xl border border-gray-200 p-5" data-business-card>
            <h2 class="text-lg font-semibold">Hasil sejak mulai pembukuan</h2>
            <p class="mt-1 text-sm" data-business-muted>{{ $report['start_date']->locale('id')->translatedFormat('d F Y') }} sampai {{ $report['end']->locale('id')->translatedFormat('d F Y') }}. Data sebelum mulai pembukuan tidak termasuk.</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-3">
                @foreach([['Total penjualan', $lifetime['totals']['sales']], ['Akumulasi laba / rugi', $lifetime['net_profit']], ['Kekayaan bersih (aset - utang)', $report['asset_total'] - $report['liability_total']]] as [$label, $value])
                    <div><p class="text-sm" data-business-muted>{{ $label }}</p><p class="mt-2 break-words text-xl font-bold tabular-nums">{{ $money::format($value) }}</p></div>
                @endforeach
            </div>
            <p class="mt-4 text-sm leading-6" data-business-muted>{{ $lifetime['net_profit'] > 0 ? 'Usaha mencatat laba kumulatif.' : ($lifetime['net_profit'] < 0 ? 'Usaha mencatat rugi kumulatif.' : 'Belum ada laba bersih tercatat.') }} Laba bukan saldo kas, dan sudah termasuk pendapatan lain.</p>
            @if($report['missing_cogs'] || $lifetime['missing_cogs'])<p class="mt-3 rounded-lg border border-amber-500 p-3 text-sm leading-6"><strong>Perhatian:</strong> ada penjualan tanpa HPP tercatat pada periode atau rentang kumulatif. Laba belum andal sebelum harga pokok dan biaya dilengkapi.</p>@endif
            <p class="mt-3 text-xs" data-business-muted>Posisi aset dan kewajiban per {{ $report['end']->locale('id')->translatedFormat('d F Y') }}; bukan selalu saldo hari ini.</p>
        </section>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([['Penjualan periode', $report['totals']['sales'], 'shopping-bag', 'text-blue-600'], ['Laba / rugi bersih', $profit, 'trending-up', $profit >= 0 ? 'text-emerald-600' : 'text-red-600'], ['Kas & bank', $cash, 'wallet', 'text-violet-600'], ['Total aset', $report['asset_total'], 'landmark', 'text-amber-600']] as [$label, $value, $icon, $color])
                <div class="rounded-2xl border border-gray-200 bg-white p-4 text-gray-800 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 sm:p-5" data-business-card><div class="flex items-start justify-between gap-2"><p class="text-xs leading-5 text-gray-600 dark:text-gray-400 sm:text-sm">{{ $label }}</p><i data-lucide="{{ $icon }}" class="h-5 w-5 shrink-0 {{ $color }}"></i></div><p class="mt-3 break-words text-base font-bold tabular-nums sm:text-xl">{{ $money::format($value) }}</p></div>
            @endforeach
        </div>
        <div class="grid gap-5 xl:grid-cols-5">
            <section class="rounded-2xl border border-gray-200 p-5 xl:col-span-3" data-business-card>
                <h2 class="font-semibold">Tren performa</h2>
                <p class="mt-1 text-sm" data-business-muted>12 bulan sampai {{ \Illuminate\Support\Carbon::parse($month.'-01')->locale('id')->translatedFormat('F Y') }}. Geser grafik dan tabel untuk melihat seluruh bulan.</p>
                <div class="mt-4 flex flex-wrap gap-4 text-xs"><span><span style="color:#10b981">?</span> Penjualan</span><span><span style="color:#f59e0b">?</span> HPP & biaya</span></div>
                <div class="mt-5 overflow-x-auto pb-3" tabindex="0" role="region" aria-label="Grafik penjualan dan biaya bulanan">
                    <div class="business-chart" aria-hidden="true">
                        @foreach($trend as $item)
                            <div class="business-chart-month"><div class="business-chart-bars"><div class="business-chart-bar" style="background:#10b981;height:{{ $item['sales'] != 0 ? max(2, abs($item['sales']) / $maxTrend * 100) : 0 }}%"></div><div class="business-chart-bar" style="background:#f59e0b;height:{{ $item['expense'] != 0 ? max(2, abs($item['expense']) / $maxTrend * 100) : 0 }}%"></div></div><p class="mt-2 whitespace-nowrap text-xs" data-business-muted>{{ $item['label'] }}</p></div>
                        @endforeach
                    </div>
                </div>
                <p class="mt-2 text-xs" data-business-muted>Tinggi batang menunjukkan besar nilai absolut; angka negatif (koreksi/retur) ditampilkan bertanda minus pada tabel.</p>
                <div class="mt-4 overflow-x-auto" tabindex="0" role="region" aria-label="Rincian angka performa bulanan">
                    <table class="business-trend w-full text-left text-xs" style="min-width:600px"><caption class="sr-only">Penjualan, HPP dan biaya, serta laba rugi per bulan</caption><thead><tr><th class="p-3">Bulan</th><th class="p-3 text-right">Penjualan</th><th class="p-3 text-right">HPP & biaya</th><th class="p-3 text-right">Laba / rugi</th><th class="p-3">Rincian</th></tr></thead><tbody>
                        @foreach($trend as $item)<tr><th class="p-3 font-medium">{{ $item['label'] }}</th><td class="p-3 text-right">{{ $money::format($item['sales']) }}</td><td class="p-3 text-right">{{ $money::format($item['expense']) }}</td><td class="p-3 text-right">{{ $money::format($item['profit']) }}</td><td class="p-3"><button type="button" class="business-detail-button" onclick="document.getElementById('business-detail-{{ $item['month'] }}').showModal()" aria-haspopup="dialog" aria-label="Detail {{ $item['title'] }}">Detail</button></td></tr>@endforeach
                    </tbody></table>
                </div>
                @if(!$trend->contains(fn ($item) => $item['sales'] != 0 || $item['expense'] != 0 || $item['profit'] != 0))<p class="business-info mt-4 rounded-lg p-3 text-sm leading-6">Belum ada nilai pada rentang ini. Grafik mengambil jurnal berstatus posted, bukan draft. Pastikan periode sesuai tanggal transaksi dan penjualan serta HPP sudah diposting.</p>@endif
            </section>
            <section class="rounded-2xl border border-gray-200 bg-white p-5 text-gray-800 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100 xl:col-span-2" data-business-card><h2 class="font-semibold">Posisi uang & aset</h2><p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Posisi per {{ $report['end']->locale('id')->translatedFormat('d F Y') }}</p><div class="mt-5 space-y-4">@foreach([['Kas & bank', $cash, 'wallet'], ['Piutang petani', $farmerReceivable, 'hand-coins'], ['Piutang pelanggan & lainnya', $receivable, 'hand-coins'], ['Persediaan / stok', $inventory, 'boxes'], ['Aset tetap setelah penyusutan', $fixed, 'building-2'], ['Uang muka & aset lainnya', $otherAssets, 'wallet']] as [$label, $value, $icon])<div class="flex items-center gap-3"><div class="rounded-lg bg-gray-100 p-2.5 text-brand-600 dark:bg-gray-800"><i data-lucide="{{ $icon }}" class="h-4 w-4"></i></div><div class="min-w-0 flex-1"><p class="text-sm text-gray-600 dark:text-gray-400">{{ $label }}</p><p class="truncate font-semibold tabular-nums">{{ $money::format($value) }}</p></div></div>@endforeach</div><div class="mt-5 flex items-center justify-between border-t border-gray-100 pt-4 text-sm dark:border-gray-800"><span class="font-medium">Total aset</span><strong class="tabular-nums">{{ $money::format($report['asset_total']) }}</strong></div></section>
        </div>
        <div class="business-info rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-900 dark:border-blue-900 dark:bg-blue-500/10 dark:text-blue-200"><strong>Piutang petani:</strong> pinjaman dibayar tunai atau dipotong saat panen sesuai kesepakatan, tidak wajib lunas satu panen. Pokok pinjaman bukan biaya atau laba.<br><strong>Catatan:</strong> laba dihitung dari jurnal yang sudah diposting. Penjualan yang belum dibayar tetap tercermin sebagai piutang, bukan sebagai kas.</div>
        @include('pages.business-process.partials.monthly-details')
    @endif
</div>
@endsection







