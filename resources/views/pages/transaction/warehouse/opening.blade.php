@extends('layouts.app')
@section('title', 'Stok Awal Gudang')
@section('breadcrumb')<x-breadcrumb pageTitle="Stok Awal Gudang" :links="[['name'=>'Manajemen Gudang','url'=>route('manajemen-gudang.index')]]" />@endsection
@section('content')
@php($input = 'mt-2 w-full rounded-lg border border-gray-300 bg-transparent p-3 dark:border-gray-700 dark:bg-gray-900')
<div class="space-y-6 text-gray-800 dark:text-gray-200">
<div><h2 class="text-xl font-semibold">Stok Awal Gudang</h2><p class="mt-2 text-sm text-gray-500">Pilih gudang, lalu isi karung dan kilo barang yang sudah ada pada setiap area. Setiap penyimpanan menambah stok.</p></div>
@if(session('success'))<div role="status" class="rounded-lg bg-green-50 p-4 text-green-700">{{ session('success') }}</div>@endif
@if($errors->any())<ul role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
@can('opening-stock.create')
<form method="POST" action="{{ route('stok-awal.store') }}" class="space-y-5 rounded-xl border bg-white p-6 dark:border-gray-800 dark:bg-gray-900" x-data="{ saving: false, usage: @js(old('usage', '')) }" @submit="saving = true">
@csrf
<input type="hidden" name="token" value="{{ old('token', (string) \Illuminate\Support\Str::uuid()) }}">
<div class="grid gap-4 sm:grid-cols-2">
<label>Gudang<select name="usage" required x-model="usage" class="{{ $input }}"><option value="">Pilih gudang</option><option value="receiving">Gudang Penerimaan</option><option value="finished">Gudang Ready</option></select></label>
<label>Tanggal Stok Awal<input type="date" name="stock_date" required max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" value="{{ old('stock_date', now('Asia/Jakarta')->format('Y-m-d')) }}" class="{{ $input }}"></label>
</div>
@foreach(['receiving'=>'Gudang Penerimaan', 'finished'=>'Gudang Ready'] as $usage=>$heading)
<section x-show="usage === '{{ $usage }}'" x-cloak class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
<h3 class="bg-gray-50 px-4 py-3 font-semibold dark:bg-gray-800">{{ $heading }}</h3>
@forelse($areas->where('usage', $usage) as $area)
@php($previous = collect(old('items', []))->firstWhere('warehouse_area_id', $area->id))
<div class="flex flex-wrap items-center gap-3 border-t px-4 py-3 dark:border-gray-700">
<span class="w-28 shrink-0 text-sm font-medium">{{ $area->name }}</span>
<input type="hidden" name="items[{{ $area->id }}][warehouse_area_id]" value="{{ $area->id }}" :disabled="usage !== '{{ $usage }}'">
<label class="flex min-w-0 flex-1 items-center gap-2"><input type="number" name="items[{{ $area->id }}][package_count]" min="0" max="1000000" step="1" value="{{ $previous['package_count'] ?? '' }}" :disabled="usage !== '{{ $usage }}'" aria-label="{{ $area->name }} Karung" class="min-w-0 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"><span class="text-sm text-gray-500">Karung</span></label>
<label class="flex min-w-0 flex-1 items-center gap-2"><input type="number" name="items[{{ $area->id }}][weight_kg]" min="0" max="9999999999.99" step="0.01" value="{{ $previous['weight_kg'] ?? '' }}" :disabled="usage !== '{{ $usage }}'" aria-label="{{ $area->name }} Kg" class="min-w-0 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900"><span class="text-sm text-gray-500">Kg</span></label>
</div>
@empty<p class="p-4 text-sm text-gray-500">Belum ada area aktif untuk gudang ini.</p>@endforelse
</section>
@endforeach
<p x-show="usage" x-cloak class="text-sm text-gray-500">Area yang kosong atau nol tidak dicatat. Isi karung dan kilo pada area yang memiliki stok.</p>
<label class="block">Catatan (opsional)<textarea name="notes" maxlength="2000" rows="2" class="{{ $input }}">{{ old('notes') }}</textarea></label>
<button type="submit" :disabled="saving || !usage" class="rounded-lg bg-brand-600 px-5 py-3 text-white disabled:opacity-50" x-text="saving ? 'Menyimpan...' : 'Simpan Stok Awal'">Simpan Stok Awal</button>
</form>
@endcan
<section class="overflow-hidden rounded-xl border bg-white dark:border-gray-800 dark:bg-gray-900">
<h3 class="p-5 text-lg font-semibold">Riwayat Input Stok Awal</h3>
<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-y bg-gray-50 dark:border-gray-800 dark:bg-gray-800"><tr>@foreach(['Tanggal','Gudang / Area','Karung','Kilo (Kg)','Dicatat Oleh','Catatan'] as $heading)<th class="whitespace-nowrap p-3">{{ $heading }}</th>@endforeach</tr></thead><tbody>
@forelse($entries as $entry)<tr class="border-b dark:border-gray-800"><td class="whitespace-nowrap p-3">{{ \Illuminate\Support\Carbon::parse($entry->stock_date)->format('d/m/Y') }}</td><td class="p-3">{{ $entry->usage === 'receiving' ? 'Penerimaan' : 'Ready' }} / {{ $entry->area_name }}</td><td class="p-3 text-right">{{ number_format($entry->package_count, 0, ',', '.') }}</td><td class="p-3 text-right">{{ number_format($entry->weight_kg, 2, ',', '.') }}</td><td class="p-3">{{ $entry->created_by_name }}</td><td class="p-3">{{ $entry->notes ?: '-' }}</td></tr>@empty<tr><td colspan="6" class="p-6 text-center text-gray-500">Belum ada stok awal yang dicatat.</td></tr>@endforelse
</tbody></table></div><div class="p-4">{{ $entries->links() }}</div>
</section>
</div>
@endsection
