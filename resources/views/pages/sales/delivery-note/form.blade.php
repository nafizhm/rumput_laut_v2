@extends('layouts.app')
@section('title', $note ? 'Edit Surat Jalan' : 'Tambah Surat Jalan')
@section('breadcrumb')<x-breadcrumb :pageTitle="$note ? 'Edit Surat Jalan' : 'Tambah Surat Jalan'" :links="[['name'=>'Surat Jalan','url'=>route('surat-jalan.index')]]" />@endsection
@section('content')
@php($input='mt-1.5 w-full rounded-lg border border-gray-300 bg-white p-3 text-sm dark:border-gray-700 dark:bg-gray-900')
<form x-data="shipmentPage()" @submit.prevent="submit" class="space-y-5 text-gray-800 dark:text-gray-200">
<div class="flex justify-between gap-3"><h2 class="text-xl font-semibold">{{ $note ? 'Edit Surat Jalan' : 'Tambah Surat Jalan' }}</h2><a href="{{ route('surat-jalan.index') }}" class="text-sm text-brand-600">Kembali ke Surat Jalan</a></div>
<fieldset :disabled="form.processing" class="space-y-5">
<section class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><h3 class="mb-4 font-semibold">Penjualan &amp; Pengiriman</h3><div class="grid gap-4 sm:grid-cols-2">
<label class="text-sm">Nota penjualan<select x-model="form.data.sales_transaction_id" @change="selectSale" required :disabled="editMode" class="{{ $input }}"><option value="">Pilih penjualan yang sudah dicatat</option><template x-for="sale in sales" :key="sale.id"><option :value="sale.id" x-text="sale.label"></option></template></select><span class="text-xs text-error-500" x-text="form.getError('sales_transaction_id')"></span></label>
<label class="text-sm">Tanggal surat jalan<input type="date" x-model="form.data.delivery_date" required class="{{ $input }}"><span class="text-xs text-error-500" x-text="form.getError('delivery_date')"></span></label>
<label class="text-sm">Penerima<input x-model="form.data.recipient_name" maxlength="255" required class="{{ $input }}"></label>
<label class="text-sm">Alamat tujuan<input x-model="form.data.destination_address" maxlength="2000" required class="{{ $input }}"></label>
</div><p class="mt-3 text-xs text-gray-500">Nomor Surat Jalan otomatis saat disimpan. Draft belum mengurangi stok.</p></section>
<section class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><h3 class="mb-4 font-semibold">Barang yang Dikirim</h3><p x-show="!form.data.items.length" class="py-5 text-sm text-gray-500">Pilih nota penjualan untuk menampilkan barangnya.</p>
<div class="space-y-3"><template x-for="(row,index) in form.data.items" :key="row.sales_transaction_id"><div class="rounded-lg border p-4 dark:border-gray-700"><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
<label class="text-sm">Deskripsi barang<input x-model="row.item_name" maxlength="255" required class="{{ $input }}"></label>
<label class="text-sm">Berat kirim (Kg)<input type="number" x-model="row.quantity_kg" min="0.01" max="999999999.99" step="0.01" required class="{{ $input }}"></label>
<label class="text-sm">Jumlah kemasan<input type="number" x-model="row.sack_count" min="1" max="1000000" step="1" required class="{{ $input }}"></label>
<label x-show="sourceItem(row)?.select_batch" class="text-sm">Stok siap jual<select x-model="row.finished_batch_id" :required="sourceItem(row)?.select_batch" class="{{ $input }}"><option value="">Pilih area / batch</option><template x-for="batch in availableBatches(row)" :key="batch.id"><option :value="batch.id" :selected="String(batch.id) === String(row.finished_batch_id)" x-text="batch.label"></option></template></select></label>
</div><div class="mt-3 flex justify-between gap-3"><p class="text-xs text-gray-500" x-text="'Sisa penjualan: ' + formatWeight(sourceItem(row)?.quantity_kg || 0)"></p><button type="button" @click="form.data.items.splice(index,1)" class="text-sm text-red-600">Hapus dari pengiriman</button></div>
<p class="mt-2 text-xs text-error-500" x-text="rowError(index)"></p></div></template></div>
<p class="mt-3 text-sm text-error-500" x-text="form.getError('items')"></p></section>
<section class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><h3 class="mb-4 font-semibold">Rincian Pengiriman</h3><div class="grid gap-4 sm:grid-cols-2">
<label class="text-sm">Asal barang<input x-model="form.data.origin" maxlength="255" required class="{{ $input }}"></label>
<label class="text-sm">Kemasan<input x-model="form.data.packaging" maxlength="255" placeholder="Contoh: Karung" class="{{ $input }}"></label>
<label class="text-sm">Nama pengirim<input x-model="form.data.sender_name" maxlength="255" class="{{ $input }}"></label>
<label class="text-sm">Alamat pengirim<textarea x-model="form.data.origin_address" maxlength="2000" rows="2" class="{{ $input }}"></textarea></label>
<label class="text-sm">No. telepon pengirim<input type="tel" x-model="form.data.sender_contact" maxlength="255" class="{{ $input }}"></label>
<label class="text-sm">Diterima oleh<input x-model="form.data.receiver_name" maxlength="255" class="{{ $input }}"></label>
<label class="text-sm">Nomor kendaraan<input x-model="form.data.vehicle_plate" maxlength="30" class="{{ $input }}"></label>
<label class="text-sm">Nama sopir<input x-model="form.data.driver_name" maxlength="255" class="{{ $input }}"></label>
<label class="text-sm">Catatan<textarea x-model="form.data.notes" maxlength="5000" rows="2" class="{{ $input }}"></textarea></label>
</div></section>
<div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900"><p class="text-sm" x-text="form.data.items.length + ' barang / ' + formatWeight(totalWeight)"></p><div class="flex gap-3"><a href="{{ route('surat-jalan.index') }}" class="rounded-lg border px-4 py-3 text-sm">Batal</a><button type="submit" :disabled="form.processing || !form.data.items.length" class="rounded-lg bg-brand-600 px-5 py-3 text-sm text-white disabled:opacity-50" x-text="form.processing ? 'Menyimpan...' : 'Simpan Draft Surat Jalan'"></button></div></div>
<p role="alert" class="text-sm text-error-500" x-text="serverError"></p>
</fieldset></form>
@endsection
@push('scripts')
<script>
function shipmentPage() { return {
sales: @js($sales), batches: @js($batches), saved: @js($note), editMode: @js((bool)$note), serverError: '',
form: useForm({number:'',sales_transaction_id:'', delivery_date:window.getLocalDateInputValue(), recipient_name:'',destination_address:'',origin:'Gudang Siap Jual',packaging:'',sender_name:'',origin_address:'',sender_contact:'',receiver_name:'',vehicle_plate:'',driver_name:'',notes:'',items:[]}),
init() { if (this.saved) { Object.keys(this.form.data).forEach(key=>{ if(key!=='items' && this.saved[key]!=null) this.form.data[key]=this.saved[key]; }); this.form.data.items=this.saved.items?.length ? this.saved.items.map(row=>this.cleanRow(row)) : [this.cleanRow(this.saved)]; } },
cleanRow(row) { return {sales_transaction_id:row.sales_transaction_id,item_name:row.item_name,quantity_kg:row.quantity_kg,sack_count:row.sack_count,finished_batch_id:row.finished_batch_id == null ? '' : String(row.finished_batch_id)}; },
get selectedSale() { return this.sales.find(sale=>sale.id==this.form.data.sales_transaction_id); },
sourceItem(row) { return this.selectedSale?.items.find(item=>item.sales_transaction_id==row.sales_transaction_id); },
availableBatches(row) { const grade=this.sourceItem(row)?.quality_grade_id; return this.batches.filter(batch=>String(batch.id)===String(row.finished_batch_id) || !grade || batch.quality_grade_id==grade); },
selectSale() { const sale=this.selectedSale; this.form.data.recipient_name=sale?.recipient_name || '';this.form.data.destination_address=sale?.destination_address || ''; this.form.data.items=(sale?.items || []).filter(item=>item.quantity_kg>0 && item.sack_count>0).map(item=>this.cleanRow(item)); this.form.clearErrors(); },
get totalWeight() { return this.form.data.items.reduce((sum,row)=>sum+Number(row.quantity_kg || 0),0); },
rowError(index) { return Object.entries(this.form.errors).filter(([key])=>key==='items.'+index || key.startsWith('items.'+index+'.')).map(([,value])=>value).join(' '); },
async submit() { this.serverError=''; await this.form.submit(this.editMode?'put':'post', this.editMode ? @js(url('surat-jalan')).concat('/',this.saved.id) : @js(route('surat-jalan.store')), {onSuccess:()=>window.location.href=@js(route('surat-jalan.index')),onError:error=>{ this.serverError=error.response?.data?.message || 'Periksa isian Surat Jalan.'; }}); }
}; }
</script>
@endpush
