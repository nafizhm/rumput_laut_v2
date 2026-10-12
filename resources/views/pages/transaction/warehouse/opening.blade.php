@extends('layouts.app')
@section('title', 'Stok Awal Gudang')
@section('breadcrumb')<x-breadcrumb pageTitle="Stok Awal Gudang" :links="[['name'=>'Manajemen Gudang','url'=>route('manajemen-gudang.index')]]" />@endsection
@section('content')
@php($input = 'mt-2 w-full rounded-lg border border-gray-300 bg-transparent p-3 dark:border-gray-700 dark:bg-gray-900')
<div class="space-y-6 text-gray-800 dark:text-gray-200">
<div><h2 class="text-xl font-semibold">Stok Awal Gudang</h2><p class="mt-2 text-sm text-gray-500">Catat barang yang sudah ada sebelum menggunakan aplikasi. Pilih area gudang penerimaan atau gudang ready. Setiap penyimpanan menambah stok, jadi catat setiap barang satu kali.</p></div>
@if(session('success'))<div role="status" class="rounded-lg bg-green-50 p-4 text-green-700">{{ session('success') }}</div>@endif
@if($errors->any())<ul role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
@can('opening-stock.create')
<form method="POST" action="{{ route('stok-awal.store') }}" class="space-y-5 rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900" x-data="{ saving: false }" @submit="saving = true">
@csrf
<input type="hidden" name="token" value="{{ old('token', (string) \Illuminate\Support\Str::uuid()) }}">
<div class="grid gap-4 sm:grid-cols-2">
<label>Tanggal Stok Awal<input type="date" name="stock_date" required max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" value="{{ old('stock_date', now('Asia/Jakarta')->format('Y-m-d')) }}" class="{{ $input }}"></label>
<label>Gudang / Area<select name="warehouse_area_id" required class="{{ $input }}"><option value="">Pilih area gudang</option>@foreach(['receiving'=>'Gudang Penerimaan', 'finished'=>'Gudang Ready'] as $usage=>$label)<optgroup label="{{ $label }}">@foreach($areas->where('usage', $usage) as $area)<option value="{{ $area->id }}" @selected(old('warehouse_area_id') == $area->id)>{{ $area->name }} — {{ $area->inventory->item_name }}</option>@endforeach</optgroup>@endforeach</select></label>
<label>Kualitas Barang<select name="quality_grade_id" required class="{{ $input }}"><option value="">Pilih kualitas barang</option>@foreach($grades as $grade)<option value="{{ $grade->id }}" @selected(old('quality_grade_id') == $grade->id)>{{ $grade->code }} — {{ $grade->name }}</option>@endforeach</select></label>
<label>Berat Aktual (Kg)<input type="number" name="weight_kg" required min="0.01" max="9999999999.99" step="0.01" value="{{ old('weight_kg') }}" class="{{ $input }}"></label>
<label>Jumlah Karung / Kemasan<input type="number" name="package_count" required min="1" max="1000000" step="1" value="{{ old('package_count') }}" class="{{ $input }}"><span class="mt-1 block text-xs text-gray-500">Karung untuk penerimaan, kemasan siap jual untuk ready.</span></label>
</div>
<label class="block">Keterangan<textarea name="notes" maxlength="2000" rows="2" class="{{ $input }}">{{ old('notes') }}</textarea></label>
<p class="text-sm text-gray-500">Setelah disimpan, barang langsung tersedia di gudang. Input ini mencatat stok fisik; nilai persediaan keuangan dicatat melalui saldo awal keuangan.</p>
<button type="submit" :disabled="saving" class="rounded-lg bg-brand-600 px-5 py-3 text-white disabled:opacity-50" x-text="saving ? 'Menyimpan...' : 'Simpan Stok Awal'">Simpan Stok Awal</button>
</form>
@endcan
<section class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
<h3 class="p-5 text-lg font-semibold">Riwayat Input Stok Awal</h3>
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-y bg-gray-50 dark:border-gray-800 dark:bg-gray-800"><tr>@foreach(['Tanggal','Gudang / Area','Kualitas','Berat (Kg)','Kemasan','Dicatat Oleh','Keterangan'] as $heading)<th class="whitespace-nowrap p-3">{{ $heading }}</th>@endforeach</tr></thead><tbody>
@forelse($entries as $entry)<tr class="border-b dark:border-gray-800"><td class="whitespace-nowrap p-3">{{ \Illuminate\Support\Carbon::parse($entry->stock_date)->format('d/m/Y') }}</td><td class="p-3">{{ $entry->usage === 'receiving' ? 'Penerimaan' : 'Ready' }} / {{ $entry->area_name }}</td><td class="p-3">{{ $entry->grade_name }}</td><td class="p-3 text-right">{{ number_format($entry->weight_kg, 2, ',', '.') }}</td><td class="p-3 text-right">{{ number_format($entry->package_count, 0, ',', '.') }}</td><td class="p-3">{{ $entry->created_by_name }}</td><td class="p-3">{{ $entry->notes ?: '—' }}</td></tr>@empty<tr><td colspan="7" class="p-6 text-center text-gray-500">Belum ada stok awal yang dicatat.</td></tr>@endforelse
</tbody></table></div><div class="p-4">{{ $entries->links() }}</div>
</section>
</div>
@endsection
