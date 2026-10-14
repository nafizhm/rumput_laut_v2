@push('styles')
<style>
.business-detail-button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:8px 16px;border:1px solid #047857;border-radius:8px;background:#ecfdf5;color:#065f46;font-weight:600;cursor:pointer}
.business-detail-modal{position:fixed;inset:0;margin:auto;width:min(760px,calc(100% - 32px));max-height:90dvh;padding:0;border:1px solid #d0d5dd;border-radius:16px;background:#fff;color:#182230;overflow:auto;overscroll-behavior:contain}
.business-detail-modal::backdrop{background:rgb(0 0 0 / .65)}
.business-detail-header{position:sticky;top:0;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px;background:#fff;border-bottom:1px solid #d0d5dd}
.business-detail-body{padding:20px}
.business-detail-summary>div,.business-detail-account{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;padding:10px 0;overflow-wrap:anywhere}
.business-detail-summary>div{border-bottom:1px solid #d0d5dd;font-size:14px}
.business-detail-section{padding:14px;border:1px solid #d0d5dd;border-radius:10px;font-size:14px}
.business-detail-section summary{cursor:pointer;font-weight:600;min-height:32px}
html.dark .business-detail-modal,html.dark .business-detail-header{background:#182230;color:#f9fafb;border-color:#475467}
html.dark .business-detail-section,html.dark .business-detail-summary>div{border-color:#475467}
html.dark .business-detail-button{background:#064e3b;color:#d1fae5;border-color:#34d399}
.business-detail-button:focus-visible,.business-detail-section summary:focus-visible{outline:3px solid #10b981;outline-offset:3px}
@media(max-width:640px){.business-detail-modal{width:100%;height:100dvh;max-height:100dvh;border-radius:0}.business-detail-header,.business-detail-body{padding:16px}}
</style>
@endpush
@foreach($trend as $item)
    <dialog id="business-detail-{{ $item['month'] }}" class="business-detail-modal" aria-labelledby="business-detail-title-{{ $item['month'] }}">
        <header class="business-detail-header">
            <div><h2 id="business-detail-title-{{ $item['month'] }}" class="text-lg font-semibold">Rincian Performa ? {{ $item['title'] }}</h2><p class="mt-1 text-xs" data-business-muted>Jurnal posted pada bulan ini.</p></div>
            <form method="dialog"><button class="business-detail-button" aria-label="Tutup rincian" autofocus>Tutup</button></form>
        </header>
        <div class="business-detail-body">
            @if(!$item['ready'])
                <p class="business-info rounded-lg p-4 text-sm">Periode sebelum tanggal mulai pembukuan. Laporan belum tersedia.</p>
            @else
                <dl class="business-detail-summary">
                    @foreach(['Penjualan bersih' => $item['sales'], 'HPP barang terjual' => $item['totals']['cogs'], 'Laba kotor' => $item['gross_profit'], 'Biaya operasional' => $item['totals']['operating'], 'Pendapatan lain' => $item['totals']['other_income'], 'Biaya keuangan' => $item['totals']['finance'], 'Biaya lain' => $item['totals']['other_expense'], 'Pajak' => $item['totals']['tax'], 'Laba / rugi bersih' => $item['profit']] as $label => $value)
                        <div><dt>{{ $label }}</dt><dd class="font-semibold tabular-nums">{{ $money::format($value) }}</dd></div>
                    @endforeach
                </dl>
                @if($item['missing_cogs'])<p class="business-info mt-4 rounded-lg p-3 text-sm">Ada penjualan tetapi belum ada akun HPP tercatat. Laba belum andal sebelum HPP dilengkapi.</p>@endif
                <p class="business-info mt-4 rounded-lg p-3 text-sm leading-6">Bahan yang belum terjual tetap persediaan, bukan HPP. Biaya pengolahan dalam HPP tidak dihitung ulang sebagai operasional. Rincian mengikuti akun jurnal; biaya beli dan pengolahan tidak diperkirakan jika pencatatannya belum terpisah.</p>
                <div class="mt-4 space-y-3">
                    @foreach(['sales' => 'Penjualan bersih', 'cogs' => 'HPP ? biaya beli / pengolahan barang terjual', 'operating' => 'Biaya operasional', 'other_income' => 'Pendapatan lain', 'finance' => 'Biaya keuangan', 'other_expense' => 'Biaya lain', 'tax' => 'Pajak'] as $section => $label)
                        <details class="business-detail-section" @if(in_array($section, ['cogs', 'operating'])) open @endif>
                            <summary>{{ $label }} ? {{ $money::format($item['totals'][$section]) }}</summary>
                            <dl class="mt-3 space-y-3">
                                @forelse($item['sections'][$section] as $account)
                                    <div class="business-detail-account"><dt>{{ $account['code'] }} ? {{ $account['name'] }}</dt><dd class="font-semibold tabular-nums">{{ $money::format($account['amount']) }}</dd></div>
                                @empty
                                    <div><dt class="text-sm" data-business-muted>Belum ada akun tercatat pada bagian ini.</dt><dd class="sr-only">Tidak ada rincian</dd></div>
                                @endforelse
                            </dl>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>
    </dialog>
@endforeach
