@extends('layouts.app')

@section('title', 'Dashboard Gudang')

@section('breadcrumb')
    <x-breadcrumb pageTitle="Dashboard Gudang" :links="[['name' => 'Manajemen Gudang', 'url' => route('manajemen-gudang.index')]]" />
@endsection

@section('content')
    @php
        $stageDetails = [
            'incoming' => ['icon' => 'package-open', 'description' => 'Barang diterima, ditimbang, dan dipisahkan menurut kualitas serta area.', 'color' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300'],
            'process' => ['icon' => 'wind', 'description' => 'Karung dari satu atau beberapa batch masuk proses blower atau jemur.', 'color' => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-300'],
            'wip' => ['icon' => 'hourglass', 'description' => 'Barang selesai diproses dan menunggu press. Berat resmi belum ditentukan.', 'color' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'],
            'press' => ['icon' => 'scale', 'description' => 'Press, timbang aktual, dan packing diselesaikan dalam satu pencatatan.', 'color' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-300'],
            'finished' => ['icon' => 'package-check', 'description' => 'Hasil jadi disimpan menurut kualitas dan area, lalu tersedia untuk penjualan.', 'color' => 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300'],
        ];
        $metrics = [
            ['label' => 'Karung Belum Diproses', 'value' => number_format($sacks, 0, ',', '.'), 'unit' => 'karung', 'note' => 'Sisa bahan dari batch penerimaan', 'url' => route('manajemen-gudang.stage', 'incoming')],
            ['label' => 'Batch Penerimaan', 'value' => number_format($batchCount, 0, ',', '.'), 'unit' => 'batch', 'note' => 'Sudah dikonfirmasi diterima', 'url' => route('manajemen-gudang.stage', 'incoming')],
            ['label' => 'Total Berat Diterima', 'value' => number_format($weight, 2, ',', '.'), 'unit' => 'kg', 'note' => 'Akumulasi penerimaan, bukan berat jadi', 'url' => route('manajemen-gudang.stage', 'incoming')],
            ['label' => 'Draft Penerimaan', 'value' => number_format($drafts, 0, ',', '.'), 'unit' => 'dokumen', 'note' => 'Belum menambah stok gudang', 'url' => route('penerimaan-barang.index')],
        ];
    @endphp

    <div class="space-y-6 text-gray-800 sm:space-y-8 dark:text-gray-200">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <h2 class="text-xl leading-tight font-semibold tracking-tight text-gray-900 sm:text-2xl dark:text-white">Barang Datang sampai Barang Jadi</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-400">Pantau penerimaan bahan, alur pekerjaan, dan lokasi penyimpanan dalam satu halaman.</p>
            </div>
            <div class="grid shrink-0 gap-3 sm:flex sm:flex-wrap">
                @can('receiving.create')
                    <a href="{{ route('penerimaan-barang.create') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                        <i data-lucide="plus" class="h-4 w-4 shrink-0" aria-hidden="true"></i>
                        Penerimaan Barang
                    </a>
                @endcan
                <a href="{{ route('manajemen-gudang.movements') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                    <i data-lucide="history" class="h-4 w-4 shrink-0" aria-hidden="true"></i>
                    Riwayat Mutasi
                </a>
            </div>
        </div>

        <section aria-label="Ringkasan penerimaan" class="grid grid-cols-1 gap-3 min-[375px]:grid-cols-2 sm:gap-4 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <a href="{{ $metric['url'] }}" class="min-w-0 rounded-2xl border border-gray-200 bg-white p-4 transition hover:border-brand-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:p-5 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-brand-700">
                    <p class="min-h-10 text-sm leading-5 font-medium text-gray-600 dark:text-gray-400">{{ $metric['label'] }}</p>
                    <p class="mt-3 break-words text-2xl leading-tight font-semibold tracking-tight text-gray-900 sm:text-3xl dark:text-white">{{ $metric['value'] }}</p>
                    <p class="mt-1 text-sm font-medium text-gray-600 dark:text-gray-400">{{ $metric['unit'] }}</p>
                    <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $metric['note'] }}</p>
                </a>
            @endforeach
        </section>

        <section aria-labelledby="warehouse-flow-title">
            <div class="mb-4 flex flex-col gap-1 sm:mb-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 id="warehouse-flow-title" class="text-lg font-semibold text-gray-900 dark:text-white">Alur Stok Fisik</h3>
                    <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">Pilih tahapan untuk melihat rincian dan cara pencatatannya.</p>
                </div>
                <span class="text-xs leading-5 text-gray-500 dark:text-gray-400">5 tahapan dari penerimaan hingga siap jual</span>
            </div>
            <ol class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-3 2xl:grid-cols-5">
                @foreach ($stages as $key => $label)
                    @php($detail = $stageDetails[$key])
                    <li class="min-w-0">
                        <a href="{{ route('manajemen-gudang.stage', $key) }}" class="group flex h-full flex-col rounded-2xl border bg-white p-5 transition hover:border-brand-400 hover:shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:p-6 {{ $key === 'incoming' ? 'border-brand-300 dark:border-brand-700' : 'border-gray-200 dark:border-gray-800' }} dark:bg-gray-900">
                            <div class="flex items-center justify-between gap-3">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $detail['color'] }}"><i data-lucide="{{ $detail['icon'] }}" class="h-5 w-5" aria-hidden="true"></i></span>
                                <span class="text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400">TAHAP {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                            <h4 class="mt-4 text-base leading-6 font-semibold text-gray-900 dark:text-white">{{ $label }}</h4>
                            <p class="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-400">{{ $detail['description'] }}</p>
                            <div class="mt-4">
                                @if ($key === 'incoming')
                                    <p class="mb-3 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($sacks, 0, ',', '.') }} <span class="text-sm font-medium text-gray-500 dark:text-gray-400">karung</span></p>
                                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">{{ number_format($batchCount, 0, ',', '.') }} batch penerimaan</p>
                                    <span class="inline-flex rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">Pencatatan tersedia</span>
                                @else
                                    <p class="mb-3 text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($stageCounts[$key], 0, ',', '.') }} <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $key === 'finished' ? 'batch tersedia' : ($key === 'press' ? 'proses menunggu packing' : 'proses') }}</span></p>
                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $stageCounts[$key] > 0 ? 'Lihat pekerjaan tercatat' : 'Belum ada pekerjaan pada tahap ini' }}</span>
                                @endif
                            </div>
                            <span class="mt-auto flex items-center justify-between gap-3 pt-5 text-sm font-semibold text-brand-700 dark:text-brand-300">
                                {{ $key === 'incoming' ? 'Lihat Detail Batch' : 'Lihat Detail Tahap' }}
                                <i data-lucide="arrow-right" class="h-4 w-4 shrink-0 transition-transform group-hover:translate-x-1" aria-hidden="true"></i>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="flex items-start gap-3 rounded-xl border border-sky-200 bg-sky-50 p-4 sm:p-5 dark:border-sky-900 dark:bg-sky-500/10">
            <i data-lucide="info" class="mt-0.5 h-5 w-5 shrink-0 text-sky-700 dark:text-sky-300" aria-hidden="true"></i>
            <p class="text-sm leading-6 text-gray-700 dark:text-gray-300"><strong class="font-semibold text-gray-900 dark:text-white">Karung untuk kontrol proses, kg untuk hasil akhir.</strong> Berat resmi barang jadi berasal dari hasil press, timbang aktual, dan packing. Selisih berat tidak otomatis dianggap kehilangan.</p>
        </div>

        <section aria-labelledby="warehouse-area-title" class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-5">
                <h3 id="warehouse-area-title" class="text-lg font-semibold text-gray-900 dark:text-white">Jumlah Barang per Area</h3>
                <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-400">Sisa karung berasal dari batch yang sudah diterima. Kg di bawah merupakan total berat saat penerimaan, bukan perkiraan berat sisa atau barang jadi.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($areas as $area)
                    <a href="{{ route('manajemen-gudang.area', $area) }}" class="group min-w-0 rounded-xl border border-gray-200 p-4 transition hover:border-brand-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:p-5 dark:border-gray-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0"><p class="text-xs font-semibold text-brand-700 dark:text-brand-300">{{ $area->code }}</p><h4 class="mt-1 break-words text-base font-semibold text-gray-900 dark:text-white">{{ $area->name }}</h4></div>
                            <span class="shrink-0 rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $area->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $area->inventory->item_name }} &middot; {{ ['receiving'=>'Barang Datang','processing'=>'Proses / WIP','finished'=>'Barang Jadi'][$area->usage] }}</p>
                        @if ($area->usage === 'receiving')
                            <p class="mt-4 break-words text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($area->remaining_sacks ?? 0, 0, ',', '.') }} <span class="text-sm font-medium text-gray-500 dark:text-gray-400">karung belum diproses</span></p>
                            <div class="mt-3 grid grid-cols-2 gap-3 border-t border-gray-100 pt-3 dark:border-gray-800">
                                <div><p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Batch diterima</p><p class="mt-1 text-sm font-semibold">{{ number_format($area->batch_count, 0, ',', '.') }} batch</p></div>
                                <div><p class="text-xs leading-5 text-gray-500 dark:text-gray-400">Berat diterima kumulatif</p><p class="mt-1 break-words text-sm font-semibold">{{ number_format($area->received_weight ?? 0, 2, ',', '.') }} kg</p></div>
                            </div>
                        @else
                            <p class="mt-4 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $area->usage==='processing' ? number_format($area->process_count ?? 0).' proses / '.number_format($area->process_sacks ?? 0).' karung sumber dalam proses dan WIP' : number_format($area->finished_weight ?? 0,2,',','.').' kg / '.number_format($area->finished_packages ?? 0).' kemasan jadi tersedia' }}</p>
                        @endif
                        <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">Kualitas diizinkan: {{ $area->grades->pluck('name')->join(', ') ?: 'Belum ditentukan' }}</p>
                        <span class="mt-4 flex items-center justify-between gap-3 text-sm font-semibold text-brand-700 dark:text-brand-300">Lihat Detail Area<i data-lucide="arrow-right" class="h-4 w-4 shrink-0" aria-hidden="true"></i></span>
                    </a>
                @empty
                    <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">Belum ada area gudang yang dikonfigurasi.</p>
                @endforelse
            </div>
        </section>

        <section aria-labelledby="warehouse-legacy-title" class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 id="warehouse-legacy-title" class="text-lg font-semibold text-gray-900 dark:text-white">Saldo Gudang Lama</h3>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-400">Saldo sebelum alur batch baru. Kualitas, area, dan status produksinya belum diverifikasi.</p>
                </div>
                <span class="self-start whitespace-nowrap rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">Terpisah dari batch baru</span>
            </div>
            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($legacyStocks as $inventory)
                    <a href="{{ route('manajemen-gudang.inventory', $inventory) }}" class="group min-w-0 rounded-xl border border-gray-200 p-4 transition hover:border-brand-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600 sm:p-5 dark:border-gray-700 dark:hover:border-brand-700">
                        <div class="flex items-start justify-between gap-3"><h4 class="break-words text-sm leading-6 font-semibold text-gray-900 dark:text-white">{{ $inventory->item_name }}</h4><i data-lucide="warehouse" class="h-5 w-5 shrink-0 text-gray-400" aria-hidden="true"></i></div>
                        <p class="mt-3 break-words text-2xl leading-tight font-semibold text-gray-900 dark:text-white">{{ number_format($inventory->current_stock_kg, 2, ',', '.') }} <span class="text-sm font-medium text-gray-500 dark:text-gray-400">kg</span></p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ number_format($inventory->current_sack_count, 0, ',', '.') }} karung</p>
                        <span class="mt-4 flex items-center justify-between text-sm font-medium text-brand-700 dark:text-brand-300">Lihat Mutasi<i data-lucide="arrow-right" class="h-4 w-4 shrink-0" aria-hidden="true"></i></span>
                    </a>
                @empty
                    <p class="col-span-full py-3 text-sm text-gray-500 dark:text-gray-400">Belum ada saldo gudang lama.</p>
                @endforelse
            </div>
        </section>

        <details class="rounded-xl border border-gray-200 bg-white p-4 sm:p-5 dark:border-gray-800 dark:bg-gray-900">
            <summary class="min-h-6 cursor-pointer text-sm font-semibold text-gray-700 dark:text-gray-200">Aturan Pencatatan</summary>
            <ul class="mt-4 list-disc space-y-2 pl-5 text-sm leading-6 text-gray-600 dark:text-gray-400">
                <li>Draft penerimaan belum menambah stok gudang.</li>
                <li>Konfirmasi penerimaan membentuk batch bahan satu kali.</li>
                <li>Berat datang disimpan sebagai riwayat; kg tidak perlu ditebak saat karung diproses.</li>
                <li>Saldo lama tetap dipisahkan sampai kualitas, area, dan status produksi diverifikasi.</li>
            </ul>
        </details>
    </div>
@endsection
