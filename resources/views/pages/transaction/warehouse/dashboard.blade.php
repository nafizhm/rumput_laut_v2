@extends('layouts.app')
@section('title', 'Dashboard Gudang')
@section('breadcrumb')<x-breadcrumb pageTitle="Dashboard Gudang" :links="[['name'=>'Manajemen Gudang','url'=>route('manajemen-gudang.index')]]" />@endsection
@section('content')
<div class="space-y-6 text-gray-800 dark:text-gray-200">
<div class="flex flex-wrap items-center justify-between gap-3">
<div><h2 class="text-xl font-semibold">Penerimaan sampai Siap Jual</h2><p class="mt-2 text-sm text-gray-500">Pantau sisa bahan, pengambilan proses harian, dan hasil packing.</p></div>
<div class="flex flex-wrap gap-3">
@can('opening-stock.read')<a href="{{ route('stok-awal.index') }}" class="rounded-lg border bg-white px-4 py-3 text-sm dark:bg-gray-900">Stok Awal</a>@endcan
@if(auth()->user()->can('manajemen-panen.read') && auth()->user()->can('manajemen-panen.create') && auth()->user()->can('receiving.create') && auth()->user()->can('receiving.confirm'))<a href="{{ route('manajemen-panen.index') }}" class="rounded-lg bg-brand-600 px-4 py-3 text-sm text-white">Penerimaan Panen</a>@endif
@if(auth()->user()->can('production.create') && auth()->user()->can('production.confirm'))<a href="{{ route('produksi.create') }}" class="rounded-lg border bg-white px-4 py-3 text-sm dark:bg-gray-900">Catat Proses Harian</a>@endif
</div></div>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
<a href="{{ route('manajemen-gudang.stage','incoming') }}" class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><p class="text-sm text-gray-500">Sisa Bahan Penerimaan</p><p class="mt-3 text-2xl font-semibold">{{ number_format($sacks,0,',','.') }} karung</p>@if($remainingWeight !== null)<p class="mt-2 text-sm">{{ number_format($remainingWeight,2,',','.') }} Kg alokasi</p>@endif</a>
<a href="{{ route('produksi.index') }}" class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><p class="text-sm text-gray-500">Bahan Diambil untuk Proses</p><p class="mt-3 text-2xl font-semibold">{{ number_format($processedSacks,0,',','.') }} karung</p><p class="mt-2 text-sm">Total pengambilan tercatat</p></a>
<a href="{{ route('produksi.index',['status'=>'packed']) }}" class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><p class="text-sm text-gray-500">Hasil Packing Masuk</p><p class="mt-3 text-2xl font-semibold">{{ number_format($packedWeight,2,',','.') }} Kg</p><p class="mt-2 text-sm">{{ number_format($packedPackages,0,',','.') }} kemasan kumulatif</p></a>
<a href="{{ route('manajemen-gudang.stage','finished') }}" class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><p class="text-sm text-gray-500">Stok Siap Jual Tersedia</p><p class="mt-3 text-2xl font-semibold">{{ number_format($finishedWeight,2,',','.') }} Kg</p><p class="mt-2 text-sm">{{ number_format($finishedPackages,0,',','.') }} kemasan tersisa</p></a>
</div>
<section><h3 class="mb-4 text-lg font-semibold">Alur Stok Fisik</h3><div class="grid gap-4 sm:grid-cols-3">
@foreach($stages as $key=>$label)<a href="{{ $key === 'process' ? route('produksi.index') : route('manajemen-gudang.stage',$key) }}" class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><p class="text-xs text-gray-500">TAHAP {{ $loop->iteration }}</p><h4 class="mt-2 font-semibold">{{ $label }}</h4><p class="mt-3 text-sm">{{ number_format($stageCounts[$key],0,',','.') }} {{ $key === 'process' ? 'catatan proses' : 'batch tersedia' }}</p></a>@endforeach
</div></section>
<section class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><h3 class="text-lg font-semibold">Jumlah Barang per Area</h3>
<p class="mt-2 text-sm text-gray-500">Area angka untuk bahan penerimaan, area huruf untuk bahan siap jual. Kg alokasi bahan mengikuti stok asal; Kg siap jual berasal dari timbang hasil packing.</p>
@foreach(['receiving'=>'Bahan Penerimaan','finished'=>'Bahan Siap Jual'] as $usage=>$heading)
<h4 class="mb-3 mt-6 font-semibold">{{ $heading }}</h4><div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
@forelse($areas->where('usage',$usage) as $area)
<a href="{{ route('manajemen-gudang.area',$area) }}" class="rounded-xl border p-4 dark:border-gray-700"><div class="flex justify-between gap-2"><h5 class="font-semibold">{{ $areaLabels[$area->id] }}</h5>@if(!$area->is_active)<span class="text-xs text-gray-500">Nonaktif</span>@endif</div>
@if($usage === 'receiving')
<p class="mt-3 text-xl font-semibold">{{ number_format($area->remaining_sacks ?? 0,0,',','.') }} karung</p>
@if($remainingWeight !== null)<p class="mt-2 text-sm">{{ number_format($area->remaining_weight ?? 0,2,',','.') }} Kg alokasi tersisa</p>@endif
<p class="mt-3 text-xs text-gray-500">Diterima: {{ number_format($area->received_weight ?? 0,2,',','.') }} Kg / {{ $area->batch_count }} batch</p>
@else
<p class="mt-3 text-xl font-semibold">{{ number_format($area->finished_weight ?? 0,2,',','.') }} Kg</p><p class="mt-2 text-sm">{{ number_format($area->finished_packages ?? 0,0,',','.') }} kemasan tersedia</p>
@endif
</a>@empty<p class="text-sm text-gray-500">Belum ada area.</p>@endforelse
</div>@endforeach</section>
@if($drafts > 0)<a href="{{ route('penerimaan-barang.index') }}" class="block text-sm text-brand-600">{{ $drafts }} draft penerimaan belum menambah stok.</a>@endif
@if($stageCounts['wip'] > 0 || ($productionReady && $stageCounts['press'] > 0))<a href="{{ route('manajemen-gudang.stage','wip') }}" class="block text-sm text-brand-600">{{ $stageCounts['wip'] }} proses lama menunggu packing.</a>@endif
</div>
@endsection
