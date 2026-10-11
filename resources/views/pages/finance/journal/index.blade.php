@extends('layouts.app')
@section('title', $kind === 'opening' ? 'Saldo Awal' : 'Jurnal Umum')
@section('breadcrumb')
    <x-breadcrumb :pageTitle="$kind === 'opening' ? 'Saldo Awal' : 'Jurnal Umum'" :links="[['name'=>'Keuangan','url'=>'#']]" />
@endsection
@section('content')
    @php
        $money = \App\Services\Finance\AccountingMoney::class;
    @endphp
    <div x-data="journalList()" class="space-y-5 text-gray-800 dark:text-gray-200">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <x-info-help label="Informasi jurnal dan mulai pembukuan">
<p class="text-sm leading-6 text-gray-600 dark:text-gray-400">{{ $kind === 'opening' ? 'Isi saldo Harta, Utang, dan Modal yang sudah diverifikasi. Tanggal saldo awal menjadi tanggal mulai pembukuan. Laba periode sebelumnya dimasukkan ke Laba Ditahan.' : 'Transaksi petani, gudang, penjualan dan pembayaran membentuk jurnal otomatis. Gunakan jurnal manual untuk saldo, pendanaan dan penyesuaian yang belum dicatat pada menu transaksi. Draft belum masuk laporan. Hanya jurnal seimbang yang sudah diposting masuk Neraca dan Laba Rugi.' }}</p>
<p class="mt-2 text-sm text-gray-500">Mulai pembukuan: {{ $setting->start_date?->format('d/m/Y') ?? 'Belum ditetapkan — posting Saldo Awal terlebih dahulu' }}.</p>
            </x-info-help>
            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                @if($kind !== 'opening')
                    <form method="GET" action="{{ route('jurnal.index') }}" class="flex flex-wrap gap-3">
                        <input type="month" name="month" value="{{ request('month') }}" aria-label="Bulan jurnal" class="rounded-lg border border-gray-300 bg-transparent px-3 py-2 dark:border-gray-700">
                        <select name="status" aria-label="Status jurnal" class="rounded-lg border border-gray-300 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900"><option value="">Semua status</option><option value="draft" @selected(request('status')==='draft')>Draft</option><option value="posted" @selected(request('status')==='posted')>Posted</option></select>
                        <button class="rounded-lg bg-brand-600 px-4 py-2 text-white">Tampilkan</button>
                        <a href="{{ route('jurnal.index') }}" class="px-2 py-2 text-gray-500">Reset</a>
                    </form>
                @endif
                @can('journal.create')
                    @if($kind !== 'opening' || !$setting->start_date)
                        <a href="{{ route($kind==='opening'?'saldo-awal.create':'jurnal.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white">{{ $kind==='opening'?'Isi Saldo Awal':'Tambah Jurnal' }}</a>
                    @endif
                @endcan
            </div>
        </div>
        @if($kind==='opening' && !$setting->start_date)
        @can('journal.post')
        <form method="POST" action="{{ route('saldo-awal.start-zero') }}" class="space-y-3 rounded-xl border border-gray-200 bg-white p-5 dark:bg-gray-900">@csrf
            <h3 class="font-semibold">Mulai dari saldo nol</h3><x-info-help><p class="text-sm text-gray-500">Gunakan jika kas, persediaan dan saldo utang/piutang sebelum transaksi pertama benar-benar nol. Jika ada saldo lama, gunakan Isi Saldo Awal.</p></x-info-help>
            <label class="block text-sm">Tanggal mulai pembukuan<input type="date" name="entry_date" required value="{{ now('Asia/Jakarta')->format('Y-m-d') }}" max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" class="mt-2 rounded-lg border bg-transparent p-3"></label>
            <label class="flex gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required>Saya memastikan semua saldo sebelum transaksi pertama nol.</label>
            <button class="rounded-lg bg-brand-600 px-4 py-2 text-white">Tetapkan Mulai Pembukuan</button>
        </form>
        @endcan
        @endif
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full text-left text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr>@foreach(['Nomor','Tanggal','Keterangan','Debit','Kredit','Status','Detail'] as $heading)<th class="whitespace-nowrap px-4 py-3 {{ in_array($heading,['Debit','Kredit']) ? 'text-right' : '' }}">{{ $heading }}</th>@endforeach</tr></thead><tbody>
                @forelse($entries as $entry)
                    <tr class="border-t border-gray-100 dark:border-gray-800"><td class="whitespace-nowrap px-4 py-3 font-medium">{{ $entry->number }}</td><td class="whitespace-nowrap px-4 py-3">{{ $entry->entry_date->format('d/m/Y') }}</td><td class="min-w-48 px-4 py-3">{{ $entry->description }}</td><td class="text-right tabular-nums whitespace-nowrap px-4 py-3">{{ $money::format($entry->lines->sum('debit_cents')) }}</td><td class="text-right tabular-nums whitespace-nowrap px-4 py-3">{{ $money::format($entry->lines->sum('credit_cents')) }}</td><td class="px-4 py-3">{{ $entry->status==='posted'?'Posted':'Draft' }}@if($entry->reversal)<span class="block text-xs text-gray-500">Ada pembalik</span>@endif</td><td class="px-4 py-3"><button type="button" @click="showJournal(@js(route('jurnal.show', $entry)), @js($entry->number))" class="text-brand-600 dark:text-brand-300">Lihat</button></td></tr>
                @empty<tr><td colspan="7" class="p-8 text-center text-gray-500">Belum ada jurnal sesuai filter.</td></tr>@endforelse
            </tbody></table>
        </div>
        <x-modal name="journal-detail-modal" maxWidth="max-w-[1100px]">
            <h4 class="mb-6 pr-12 text-lg font-semibold" x-text="'Detail Jurnal ' + journalNumber"></h4>
            <div class="max-h-[75vh] overflow-y-auto pr-1" aria-live="polite" :aria-busy="loading">
                <p x-show="loading" class="py-8 text-center text-sm text-gray-500">Memuat detail jurnal...</p>
                <p x-show="error" x-text="error" role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300"></p>
                <div x-show="!loading && !error" x-html="detailHtml"></div>
            </div>
            <div class="mt-4 flex justify-end"><button type="button" @click="$dispatch('close-modal', 'journal-detail-modal')" class="rounded-lg border border-gray-300 px-5 py-2 text-sm dark:border-gray-700">Tutup</button></div>
        </x-modal>
    </div>
@endsection

@push('scripts')
<script>
function journalList() {
    return {
        loading: false, error: '', detailHtml: '', journalNumber: '', requestId: 0,
        async showJournal(url, number) {
            const requestId = ++this.requestId;
            this.journalNumber = number;
            this.detailHtml = '';
            this.error = '';
            this.loading = true;
            this.$dispatch('open-modal', 'journal-detail-modal');
            try {
                const target = new URL(url, window.location.href);
                target.searchParams.set('modal', '1');
                const response = await fetch(target, {headers: {'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html'}});
                if (!response.ok || response.redirected) throw new Error('Gagal memuat detail jurnal. Silakan tutup dan coba kembali.');
                const html = await response.text();
                if (requestId === this.requestId) this.detailHtml = html;
            } catch (error) {
                if (requestId === this.requestId) this.error = error.message;
            } finally {
                if (requestId === this.requestId) this.loading = false;
            }
        }
    };
}
</script>
@endpush