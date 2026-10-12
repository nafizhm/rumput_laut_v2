@extends('layouts.app')
@section('title', 'Proses Harian')
@section('breadcrumb')<x-breadcrumb pageTitle="Proses Harian" :links="[['name'=>'Manajemen Gudang','url'=>route('manajemen-gudang.index')]]" />
@endsection
@section('content')
<div class="space-y-5 rounded-xl bg-white p-6 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold">Riwayat Proses Harian</h2>
        @can('production.create')<a href="{{ route('produksi.create') }}" class="rounded-lg bg-brand-600 px-4 py-3 text-sm text-white">Catat Proses Harian</a>@endcan
    </div>
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <label class="text-sm">Status<select name="status" class="ml-3 rounded-lg border bg-white p-3 dark:bg-gray-900">
            <option value="">Semua status</option>
            @foreach(['processing'=>'Proses aktif','wip'=>'WIP','packed'=>'Selesai packing'] as $value=>$label)<option value="{{ $value }}" @selected($status===$value)>{{ $label }}</option>@endforeach
        </select></label>
        <button class="rounded-lg border px-4 py-3 text-sm">Tampilkan</button>
    </form>
    <div class="overflow-x-auto"><table class="w-full text-left text-sm">
        <thead><tr>@foreach(['Nomor','Tanggal','Area asal','Bahan diambil','Area siap jual','Hasil packing','Status','Tindakan'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>@forelse($runs as $run)<tr class="border-t border-gray-200 dark:border-gray-800">
            <td class="p-3">{{ $run->number }}</td><td class="p-3">{{ $run->started_date->format('d/m/Y') }}</td>
            <td class="p-3">{{ $run->inputs->map(fn($input) => $areaLabels[$input->batch->line->warehouse_area_id] ?? $input->batch->line->area->name)->unique()->implode(', ') }}</td>
            <td class="p-3">{{ $run->input_sacks }} karung<br><span class="text-xs text-gray-500">{{ number_format($run->input_reference_weight_kg,2,',','.') }} Kg alokasi</span></td>
            <td class="p-3">{{ $run->outputs->map(fn($output) => $areaLabels[$output->warehouse_area_id] ?? $output->area->name)->unique()->implode(', ') ?: '—' }}</td>
            <td class="p-3">@if($run->status === 'packed'){{ number_format($run->outputs->sum('weight_kg'),2,',','.') }} Kg<br>{{ $run->outputs->sum('package_count') }} kemasan @else — @endif</td>
            <td class="p-3">{{ ['processing'=>'Proses aktif','wip'=>'WIP','packed'=>'Selesai packing'][$run->status] }}</td>
            <td class="p-3"><div class="flex gap-3"><a href="{{ route('produksi.show',$run) }}" @click.prevent="$dispatch('warehouse-detail', { url: $el.href })" class="text-brand-600">Detail</a>
                @if($run->status==='processing')@can('production.update')<a href="{{ route('produksi.edit',$run) }}" class="text-brand-600">Edit</a>@endcan @endif
            </div></td>
        </tr>@empty<tr><td colspan="8" class="p-6 text-center text-gray-500">Belum ada proses pada pilihan ini.</td></tr>@endforelse</tbody>
    </table></div>
    {{ $runs->links() }}
</div>
<x-warehouse-detail-modal />
@endsection
