@extends('layouts.app')
@section('title', $entry->kind==='opening'?'Isi Saldo Awal':'Draft Jurnal Umum')
@section('breadcrumb')<x-breadcrumb :pageTitle="$entry->kind==='opening'?'Isi Saldo Awal':'Draft Jurnal Umum'" :links="[['name'=>'Keuangan','url'=>'#'],['name'=>$entry->kind==='opening'?'Saldo Awal':'Jurnal Umum','url'=>route($entry->kind==='opening'?'saldo-awal.index':'jurnal.index')]]" />@endsection
@section('content')
    @php
        $input='mt-2 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm dark:border-gray-700';
        $initialRows=old('lines',$rows);
    @endphp
    <form method="POST" action="{{ $entry->exists?route('jurnal.update',$entry):route($entry->kind==='opening'?'saldo-awal.store':'jurnal.store') }}" x-data="journalEditor(@js($initialRows))" class="space-y-5 rounded-xl border border-gray-200 bg-white p-4 text-gray-800 sm:p-6 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200">
        @csrf @if($entry->exists) @method('PUT') @endif
        @if($errors->any())<ul class="rounded-lg bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        <p class="text-sm leading-6 text-gray-500">{{ $entry->kind==='opening'?'Gunakan angka saldo awal yang sudah diperiksa. Hanya akun Harta, Utang, dan Modal. Jangan memasukkan penjualan atau beban masa lalu ke akun Pendapatan/Biaya; saldo laba awal masuk Laba Ditahan.' : 'Pilih akun sesuai transaksi. Isi salah satu sisi Debit atau Kredit pada setiap baris. Penyesuaian HPP, penyusutan, pajak, dan beban terutang dicatat berdasarkan angka yang diverifikasi.' }}</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <label class="text-sm">{{ $entry->kind==='opening'?'Tanggal Mulai Pembukuan':'Tanggal Jurnal' }}<input name="entry_date" type="date" required value="{{ old('entry_date',$entry->entry_date->format('Y-m-d')) }}" class="{{ $input }}"></label>
            <label class="text-sm">Referensi Sumber (Opsional, Unik)<input name="source_reference" maxlength="150" value="{{ old('source_reference',$entry->source_reference) }}" class="{{ $input }}" placeholder="Nomor bukti transaksi atau rekonsiliasi"></label>
        </div>
        <label class="block text-sm">Keterangan<textarea name="description" required minlength="5" maxlength="2000" rows="2" class="{{ $input }}">{{ old('description',$entry->description) }}</textarea></label>
        <div class="space-y-3">
            <template x-for="(line,index) in lines" :key="index">
                <div class="grid items-end gap-3 rounded-xl border border-gray-200 p-4 sm:grid-cols-2 xl:grid-cols-12 dark:border-gray-800">
                    <label class="text-sm sm:col-span-2 xl:col-span-6">Akun COA<select :name="'lines['+index+'][chart_of_account_id]'" x-model="line.chart_of_account_id" required class="{{ $input }} dark:bg-gray-900"><option value="">Pilih akun</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach</select></label>
                    <div class="text-sm xl:col-span-2"><x-input label="Debit (Rp)" :name="null" x-bind:id="'journal-debit-'+index" x-bind:name="'lines['+index+'][debit]'" x-model="line.debit" :isCurrency="true" :currencyDecimals="2" placeholder="0,00" /></div>
                    <div class="text-sm xl:col-span-2"><x-input label="Kredit (Rp)" :name="null" x-bind:id="'journal-credit-'+index" x-bind:name="'lines['+index+'][credit]'" x-model="line.credit" :isCurrency="true" :currencyDecimals="2" placeholder="0,00" /></div>
                    <button type="button" @click="lines.splice(index,1)" :disabled="lines.length<=2" class="min-h-11 rounded-lg border border-gray-300 px-3 text-sm text-gray-500 xl:col-span-2 dark:border-gray-700">Hapus</button>
                </div>
            </template>
        </div>
        <button type="button" @click="lines.push({chart_of_account_id:'',debit:'0',credit:'0'})" :disabled="lines.length>=100" class="min-h-11 text-sm font-medium text-brand-600 dark:text-brand-300">+ Tambah Baris</button>
        <div class="grid gap-3 rounded-xl bg-gray-50 p-4 sm:grid-cols-3 dark:bg-gray-800">
            <div><p class="text-sm text-gray-500">Total Debit</p><p class="text-right tabular-nums mt-1 break-words font-semibold" x-text="format(total('debit'))"></p></div>
            <div><p class="text-sm text-gray-500">Total Kredit</p><p class="text-right tabular-nums mt-1 break-words font-semibold" x-text="format(total('credit'))"></p></div>
            <div><p class="text-sm text-gray-500">Pemeriksaan</p><p class="mt-1 font-semibold" x-text="total('debit')===total('credit') && total('debit')>0n ? 'Seimbang' : 'Belum seimbang'"></p></div>
        </div>
        <p class="text-xs leading-5 text-gray-500">Pemisah ribuan tampil otomatis. Gunakan koma untuk desimal, misalnya 1.500.000,50. Draft boleh belum seimbang; posting harus seimbang.</p>
        <div class="flex flex-col gap-3 sm:flex-row"><button class="min-h-11 rounded-lg bg-brand-600 px-5 py-2 text-sm font-semibold text-white">Simpan Draft</button><a href="{{ route($entry->kind==='opening'?'saldo-awal.index':'jurnal.index') }}" class="min-h-11 rounded-lg border border-gray-300 px-5 py-2 text-center text-sm dark:border-gray-700">Batal</a></div>
    </form>
@endsection
@push('scripts')
<script>
function journalEditor(rows) {
    return {
        lines: rows,
        cents(value) {
            const text = String(value || '0');
            if (!/^\d{1,12}(?:\.\d{1,2})?$/.test(text)) return 0n;
            const [whole, fraction = ''] = text.split('.');
            return BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
        },
        total(side) { return this.lines.reduce((sum,line) => sum + this.cents(line[side]), 0n); },
        format(value) { return 'Rp ' + (value / 100n).toLocaleString('id-ID') + ',' + (value % 100n).toString().padStart(2, '0'); }
    };
}
</script>
@endpush
