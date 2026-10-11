@extends('layouts.app')
@section('title',$type==='balance'?'Neraca':'Laba Rugi')
@section('breadcrumb')<x-breadcrumb :pageTitle="$type==='balance'?'Neraca':'Laba Rugi'" :links="[['name'=>'Keuangan','url'=>'#']]" />@endsection
@section('content')
    @php
        $money = \App\Services\Finance\AccountingMoney::class;
    @endphp
    <div class="space-y-5 text-gray-800 dark:text-gray-200">
        <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-900">
            <div><h2 class="text-xl font-semibold">{{ $type==='balance'?'Neraca':'Laporan Laba Rugi' }}</h2><p class="mt-1 text-sm text-gray-500">{{ $type==='balance'?'Posisi per '.$report['end']->locale('id')->translatedFormat('d F Y'):'Periode '.$report['start']->locale('id')->translatedFormat('d F Y').' – '.$report['end']->locale('id')->translatedFormat('d F Y') }}</p></div>
            <form method="GET" action="{{ route($type==='balance'?'neraca.index':'laba-rugi.index') }}" class="flex flex-col gap-3 sm:flex-row"><input type="month" name="month" required value="{{ $month }}" aria-label="Bulan laporan" class="min-h-11 rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700"><button class="min-h-11 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button></form>
        </div>
        @if(!$report['ready'])
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-900 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-200"><h3 class="font-semibold">Laporan belum tersedia</h3><p class="mt-2 text-sm leading-6">{{ $report['start_date']?'Bulan yang dipilih berakhir sebelum tanggal mulai pembukuan '.$report['start_date']->format('d/m/Y').'. Data sebelum tanggal tersebut belum direkonsiliasi.':'Saldo awal belum diposting. Isi saldo terverifikasi dan tentukan tanggal mulai pembukuan sebelum membaca laporan.' }}</p><p class="mt-2 text-sm">Angka belum tersedia, bukan berarti saldo atau laba nol.</p>@can('journal.read')<a href="{{ route('saldo-awal.index') }}" class="mt-3 inline-block text-sm font-semibold underline">Buka Saldo Awal</a>@endcan</div>
        @else
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900 dark:border-sky-800 dark:bg-sky-500/10 dark:text-sky-200"><strong>Laporan internal dari jurnal Posted.</strong> Transaksi operasional belum masuk otomatis. Pastikan saldo awal, HPP, penyusutan, pajak, dan penyesuaian periode sudah lengkap. {{ $report['posted_count'] }} jurnal diposting untuk tanggal transaksi bulan ini. Pembukuan dimulai {{ $report['start_date']->format('d/m/Y') }}.</div>
            @if($type==='balance')
                <x-info-help><p class="text-sm leading-6 text-gray-500">Saldo mencakup saldo awal dan seluruh mutasi sampai akhir bulan terpilih. Angka dalam kurung merupakan saldo pengurang atau saldo negatif.</p></x-info-help>
                <div class="grid items-start gap-5 xl:grid-cols-2">
                    @include('pages.finance.reports._section',['sectionTitle'=>'Harta','sectionRows'=>$report['assets'],'sectionTotal'=>$report['asset_total']])
                    <div class="space-y-5">
                        @include('pages.finance.reports._section',['sectionTitle'=>'Utang','sectionRows'=>$report['liabilities'],'sectionTotal'=>$report['liability_total']])
                        @php
                            $equityRows=$report['equity']->all();
                            if($report['prior_profit']!==0) $equityRows[]=['code'=>'—','name'=>'Hasil Usaha Periode Sebelumnya Belum Ditutup','group'=>'Sejak awal pembukuan sampai akhir tahun sebelumnya','amount'=>$report['prior_profit']];
                            $equityRows[]=['code'=>'32102','name'=>'Laba / Rugi Tahun Berjalan','group'=>'Dihitung dari akun Pendapatan dan Biaya, bukan jurnal tambahan','amount'=>$report['year_profit']];
                        @endphp
                        @include('pages.finance.reports._section',['sectionTitle'=>'Modal','sectionRows'=>$equityRows,'sectionTotal'=>$report['equity_total']])
                    </div>
                </div>
                <div class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 sm:grid-cols-3 dark:border-gray-800 dark:bg-gray-900"><div><p class="text-sm text-gray-500">Total Harta</p><p class="text-right tabular-nums mt-2 break-words text-lg font-semibold">{{ $money::format($report['asset_total']) }}</p></div><div><p class="text-sm text-gray-500">Total Utang + Modal</p><p class="text-right tabular-nums mt-2 break-words text-lg font-semibold">{{ $money::format($report['liability_total']+$report['equity_total']) }}</p></div><div><p class="text-sm text-gray-500">Pemeriksaan Persamaan Neraca</p><p class="mt-2 font-semibold {{ $report['difference']===0?'text-brand-700 dark:text-brand-300':'text-red-600' }}">{{ $report['difference']===0?'Seimbang':'Tidak seimbang' }}</p><p class="text-right tabular-nums mt-1 text-sm">Selisih {{ $money::format($report['difference']) }}</p></div></div>
            @else
                @if($report['missing_cogs'])<p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:border-amber-800 dark:bg-amber-500/10 dark:text-amber-200">Penjualan sudah tercatat tetapi belum ada jurnal HPP pada bulan ini. Laba yang ditampilkan belum dapat dianggap laba final. Catat HPP berdasarkan angka yang sudah diverifikasi.</p>@endif
                <div class="space-y-4">
                    @include('pages.finance.reports._section',['sectionTitle'=>'Penjualan Bersih','sectionRows'=>$report['sections']['sales'],'sectionTotal'=>$report['totals']['sales']])
                    @include('pages.finance.reports._section',['sectionTitle'=>'Harga Pokok Penjualan','sectionRows'=>$report['sections']['cogs'],'sectionTotal'=>$report['totals']['cogs']])
                    <div class="flex flex-col gap-2 rounded-xl bg-gray-100 px-5 py-4 font-semibold sm:flex-row sm:justify-between dark:bg-gray-800"><span>Laba / Rugi Kotor</span><span class="text-right tabular-nums">{{ $money::format($report['gross_profit']) }}</span></div>
                    @include('pages.finance.reports._section',['sectionTitle'=>'Beban Operasional','sectionRows'=>$report['sections']['operating'],'sectionTotal'=>$report['totals']['operating']])
                    <div class="flex flex-col gap-2 rounded-xl bg-gray-100 px-5 py-4 font-semibold sm:flex-row sm:justify-between dark:bg-gray-800"><span>Laba / Rugi Usaha</span><span class="text-right tabular-nums">{{ $money::format($report['operating_profit']) }}</span></div>
                    @foreach(['other_income'=>'Pendapatan Lainnya','finance'=>'Beban Keuangan','other_expense'=>'Beban Lainnya'] as $key=>$label)
                        @include('pages.finance.reports._section',['sectionTitle'=>$label,'sectionRows'=>$report['sections'][$key],'sectionTotal'=>$report['totals'][$key]])
                    @endforeach
                    <div class="flex flex-col gap-2 rounded-xl bg-gray-100 px-5 py-4 font-semibold sm:flex-row sm:justify-between dark:bg-gray-800"><span>Laba / Rugi Sebelum Pajak</span><span class="text-right tabular-nums">{{ $money::format($report['before_tax']) }}</span></div>
                    @include('pages.finance.reports._section',['sectionTitle'=>'Beban Pajak','sectionRows'=>$report['sections']['tax'],'sectionTotal'=>$report['totals']['tax']])
                    <div class="flex flex-col gap-2 rounded-xl bg-brand-700 px-5 py-5 text-lg font-semibold text-white sm:flex-row sm:justify-between"><span>Laba / Rugi Bersih</span><span class="text-right tabular-nums break-words">{{ $money::format($report['net_profit']) }}</span></div>
                </div>
            @endif
        @endif
    </div>
@endsection
