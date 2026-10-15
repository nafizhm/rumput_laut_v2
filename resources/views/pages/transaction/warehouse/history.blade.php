@extends('layouts.app')
@section('title', 'Riwayat Mutasi Gudang')
@section('breadcrumb')
    <x-breadcrumb pageTitle="Riwayat Mutasi Gudang" :links="[['name' => 'Manajemen Gudang', 'url' => route('manajemen-gudang.index')], ['name' => 'Riwayat Mutasi']]" />
@endsection
@section('content')
@php
    $fieldClass = 'mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200';
    $columns = [
        ['key' => 'date', 'label' => 'Tanggal / Aktivitas', 'render' => 'activity', 'sortable' => true],
        ['key' => 'item', 'label' => 'Barang / Batch', 'render' => 'product'],
        ['key' => 'area', 'label' => 'Gudang / Area', 'render' => 'location'],
        ['key' => 'weight', 'label' => $tab === 'drafts' ? 'Rencana Kirim' : 'Jumlah Mutasi', 'render' => 'quantity', 'align' => 'right', 'sortable' => true],
        ['key' => 'document', 'label' => 'Dokumen / Keterangan', 'render' => 'reference'],
    ];
@endphp
<form method="GET" action="{{ route('manajemen-gudang.movements') }}" class="mb-5 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
    x-data="{ inventoryId: @js((string) ($filters['inventory_id'] ?? '')) }">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <label class="text-sm text-gray-600 dark:text-gray-300">Dari tanggal<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="{{ $fieldClass }}"></label>
        <label class="text-sm text-gray-600 dark:text-gray-300">Sampai tanggal<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="{{ $fieldClass }}"></label>
        <label class="text-sm text-gray-600 dark:text-gray-300">Gudang
            <select name="inventory_id" x-model="inventoryId" @change="$refs.area.value = ''" class="{{ $fieldClass }}">
                <option value="">Semua gudang</option>
                @foreach ($inventories as $inventory)<option value="{{ $inventory->id }}">{{ $inventory->item_name }}{{ $inventory->stock_stage === 'finished' ? ' / Ready' : '' }}</option>@endforeach
            </select>
        </label>
        <label class="text-sm text-gray-600 dark:text-gray-300">Area
            <select name="area_id" x-ref="area" class="{{ $fieldClass }}">
                <option value="">Semua area</option>
                @foreach ($areas as $area)
                    <option value="{{ $area->id }}" @selected(($filters['area_id'] ?? null) == $area->id)
                        x-show="!inventoryId || inventoryId == @js($area->inventory_id)" :disabled="!!inventoryId && inventoryId != @js($area->inventory_id)">
                        {{ $area->name }} · {{ $area->inventory?->item_name }}{{ !$area->is_active ? ' (nonaktif)' : '' }}
                    </option>
                @endforeach
            </select>
        </label>
    </div>
    @if ($errors->any())<p role="alert" class="mt-3 text-sm text-red-600">{{ $errors->first() }}</p>@endif
    <div class="mt-4 flex items-center gap-3"><button type="submit" class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm text-white">Terapkan Filter</button><a href="{{ route('manajemen-gudang.movements', ['tab' => $tab]) }}" class="text-sm text-gray-500">Reset</a></div>
</form>
<nav class="mb-4 flex gap-2" aria-label="Jenis riwayat">
    @foreach (['movements' => 'Mutasi Stok', 'drafts' => 'Draft Pengiriman'] as $key => $label)
        <a href="{{ route('manajemen-gudang.movements', array_merge($filters, ['tab' => $key])) }}" @if ($tab === $key) aria-current="page" @endif
            class="rounded-lg px-4 py-2.5 text-sm font-medium {{ $tab === $key ? 'bg-brand-600 text-white' : 'border border-gray-200 bg-white text-gray-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300' }}">{{ $label }}</a>
    @endforeach
</nav>
<p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
    @if ($tab === 'drafts')
        Rencana pengiriman belum mengurangi atau mengunci stok. Jumlah draft bukan stok terpesan. Tanggal mengikuti tanggal surat jalan. Draft tanpa batch belum bisa difilter ke area tertentu.
    @else
        Stok keluar karena pengiriman dicatat saat keberangkatan dikonfirmasi. Saldo pada detail adalah saldo gudang saat transaksi, bukan saldo area.
        Riwayat lama tanpa lokasi yang dapat ditelusuri ditandai “Area belum tercatat” dan hanya tampil saat semua area dipilih.
    @endif
</p>
<x-data-table id="movementTable" :rows="$data" :columns="$columns" :url="route('manajemen-gudang.movements', array_merge($filters, ['tab' => $tab, 'format' => 'json']))">
    <x-slot:headerAction>
        <div class="flex flex-wrap gap-2">
            @can('receiving.create')<a href="{{ route('penerimaan-barang.create') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">Penerimaan Barang</a>@endcan
            @can('manajemen-gudang.update')
                <button type="button" @click="$dispatch('open-transfer-modal')" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">Transfer Stok</button>
                <button type="button" @click="$dispatch('open-adjustment-modal')" class="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">Penyesuaian</button>
            @endcan
        </div>
    </x-slot:headerAction>
    <x-slot:activity>
        <div class="min-w-0 space-y-1 text-sm">
            <p class="text-gray-500" x-text="formatDisplayDate(item.date)"></p>
            <p class="font-medium text-gray-800 dark:text-gray-200" x-text="item.activity"></p>
            <span class="inline-block rounded px-2 py-1 text-xs" :class="item.direction === null ? 'bg-amber-100 text-amber-800' : (item.direction === 'out' ? 'bg-rose-100 text-rose-700' : 'bg-green-100 text-green-700')" x-text="item.status"></span>
        </div>
    </x-slot:activity>
    <x-slot:product><div class="min-w-0 space-y-1 text-sm"><p class="break-words font-medium text-gray-800 dark:text-gray-200" x-text="item.item"></p><p class="break-all text-xs text-gray-500" x-text="item.batch || 'Batch belum tercatat'"></p></div></x-slot:product>
    <x-slot:location>
        <div class="min-w-0 space-y-1 text-sm"><p class="text-xs text-gray-500" x-text="item.inventory"></p><p class="break-words text-gray-800 dark:text-gray-200" x-text="item.area"></p>
            <p x-show="item.counterpart" class="text-xs text-gray-500" x-text="(item.direction === 'in' ? 'Dari: ' : 'Ke: ') + (item.counterpart || '')"></p>
            <p x-show="item.inferred" class="text-xs text-gray-500">Lokasi ditelusuri dari dokumen lama</p>
        </div>
    </x-slot:location>
    <x-slot:quantity>
        <div class="space-y-1 text-right text-sm"><p class="font-medium" :class="item.direction === 'out' ? 'text-rose-600' : 'text-gray-800 dark:text-gray-200'" x-text="(item.direction === 'out' ? '− ' : (item.direction === 'in' ? '+ ' : '')) + formatWeight(item.weight)"></p><p class="text-xs text-gray-500" x-text="Number(item.packages).toLocaleString('id-ID') + ' kemasan'"></p></div>
    </x-slot:quantity>
    <x-slot:reference>
        <div class="min-w-0 space-y-1 text-sm">
            <a x-show="item.url" :href="item.url" class="break-words font-medium text-brand-600" x-text="item.document || 'Lihat dokumen'"></a>
            <p x-show="!item.url" class="break-words" x-text="item.document || '—'"></p>
            @if ($tab === 'drafts')
                <p class="break-words text-gray-600 dark:text-gray-300" x-text="item.customer"></p><p class="break-words text-xs text-gray-500" x-text="item.sale"></p>
            @else
                <details class="text-xs text-gray-500"><summary class="cursor-pointer">Detail mutasi</summary><p class="mt-1 break-words" x-text="item.description"></p><p class="mt-1" x-text="'Saldo gudang saat transaksi: ' + formatWeight(item.balance)"></p></details>
            @endif
        </div>
    </x-slot:reference>
</x-data-table>
@include('pages.transaction.warehouse._warehouse-modals', ['inventories' => $inventories->map(fn ($inventory) => ['value' => $inventory->id, 'label' => $inventory->item_name, 'current_stock' => (float) $inventory->current_stock_kg, 'current_sacks' => (int) $inventory->current_sack_count])])
@endsection
