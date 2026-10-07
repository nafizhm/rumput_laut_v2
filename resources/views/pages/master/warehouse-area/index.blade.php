@extends('layouts.app')
@section('title', 'Area Gudang')
@section('breadcrumb')<x-breadcrumb pageTitle="Area Gudang" :links="[['name'=>'Master','url'=>'#']]" />@endsection
@section('content')
<div class="rounded-xl border border-gray-200 bg-white p-5 text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-gray-500">Area adalah lokasi fisik. Kualitas yang diizinkan menentukan barang yang boleh ditempatkan di area ini.</p>@can('area.create')<a href="{{ route('area-gudang.create') }}" class="rounded-lg bg-brand-500 px-4 py-2 text-sm text-white">Tambah Area</a>@endcan</div>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>@foreach(['Kode','Nama','Gudang','Fungsi','Kualitas Diizinkan','Status','Aksi'] as $h)<th class="p-3">{{ $h }}</th>@endforeach</tr></thead><tbody>
    @foreach($areas as $area)<tr class="border-t border-gray-200 dark:border-gray-800"><td class="p-3 font-semibold">{{ $area->code }}</td><td class="p-3">{{ $area->name }}</td><td class="p-3">{{ $area->inventory->item_name }}</td><td class="p-3">{{ ['receiving'=>'Bahan Masuk','processing'=>'Proses/WIP','finished'=>'Barang Jadi'][$area->usage] }}</td><td class="p-3">{{ $area->grades->pluck('name')->join(', ') }}</td><td class="p-3">{{ $area->is_active ? 'Aktif':'Nonaktif' }}</td><td class="p-3">@can('area.update')<a href="{{ route('area-gudang.edit',$area) }}" class="text-brand-500">Edit</a>@endcan</td></tr>@endforeach
    </tbody></table></div>
</div>
@endsection
