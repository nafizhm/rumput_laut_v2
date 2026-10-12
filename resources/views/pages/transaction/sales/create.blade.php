@extends('layouts.app')
@section('title', 'Tambah Penjualan')
@section('breadcrumb')<x-breadcrumb pageTitle="Tambah Penjualan" :links="[['name'=>'Penjualan','url'=>route('manajemen-penjualan.index')]]" />@endsection
@section('content')
@php($input = 'mt-1.5 h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm dark:border-gray-700 dark:bg-gray-900')
<form x-data="salesCheckout()" @submit.prevent="submit" class="space-y-5 text-gray-800 dark:text-gray-200">
<div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-2xl font-semibold">Tambah Penjualan</h2><p class="mt-1 text-sm text-gray-500">Catat barang pesanan. Stok dipilih dan diperiksa pada Surat Jalan.</p></div><a href="{{ route('manajemen-penjualan.index') }}" class="rounded-lg border px-4 py-2 text-sm">Kembali ke Penjualan</a></div>
<fieldset :disabled="form.processing" class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
<div class="min-w-0 space-y-5">
<section class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
<h3 class="mb-4 font-semibold">Konsumen &amp; Transaksi</h3><div class="grid gap-4 sm:grid-cols-2">
<label class="text-sm">Jenis penjualan<select x-model="form.data.sale_source" @change="changeSource" class="{{ $input }}"><option value="direct">Langsung tanpa PO</option><option value="po">Melalui PO konsumen</option></select></label>
<label class="text-sm">Tanggal penjualan<input type="date" x-model="form.data.sale_date" max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" required class="{{ $input }}"><span class="text-xs text-error-500" x-text="form.getError('sale_date')"></span></label>
<label x-show="form.data.sale_source === 'po'" x-cloak class="text-sm">PO konsumen<select x-model="form.data.customer_purchase_order_id" @change="selectOrder" :required="form.data.sale_source === 'po'" class="{{ $input }}"><option value="">Pilih PO</option><template x-for="po in purchaseOrders" :key="po.id"><option :value="po.id" x-text="po.label"></option></template></select><span class="text-xs text-error-500" x-text="form.getError('customer_purchase_order_id')"></span></label>
<label class="text-sm">Konsumen<select x-model="form.data.factory_id" :disabled="form.data.sale_source === 'po'" :required="form.data.sale_source === 'direct'" class="{{ $input }}"><option value="">Pilih konsumen</option>@foreach($factories as $factory)<option value="{{ $factory->id }}">{{ $factory->name }}</option>@endforeach</select><span class="text-xs text-error-500" x-text="form.getError('factory_id')"></span></label>
</div></section>
<section class="rounded-xl border bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
<div class="mb-4 flex justify-between gap-3"><h3 class="font-semibold">Daftar Barang</h3><button type="button" x-show="form.data.sale_source === 'direct'" @click="addItem" class="text-sm font-medium text-brand-600">+ Tambah Barang</button></div>
<div class="overflow-x-auto"><table class="w-full min-w-[640px] text-left text-sm"><thead class="bg-gray-50 dark:bg-gray-800"><tr><th class="p-2">Barang</th><th class="p-2">Berat (Kg)</th><th class="p-2">Kemasan</th><th class="p-2">Harga / Kg (Rp)</th><th class="p-2 text-right">Subtotal</th><th class="p-2"></th></tr></thead>
<tbody><template x-for="(row,index) in form.data.items" :key="row.key"><tr class="border-b align-top dark:border-gray-700">
<td class="min-w-48 p-2"><input :aria-label="'Nama barang ' + (index + 1)" type="text" x-model="row.item_name" maxlength="255" required class="{{ $input }}"><span class="block text-xs text-error-500" x-text="error(index,'item_name') || form.getError('items.' + index)"></span><span x-show="row.po_remaining != null" class="block text-xs text-gray-500" x-text="'Sisa PO: ' + formatWeight(row.po_remaining || 0)"></span></td>
<td class="min-w-28 p-2"><input :aria-label="'Berat barang ' + (index + 1)" type="number" x-model="row.sold_weight_kg" min="0.01" :max="row.po_remaining ?? 999999999.99" step="0.01" required class="{{ $input }}"><span class="text-xs text-error-500" x-text="error(index,'sold_weight_kg')"></span></td>
<td class="min-w-24 p-2"><input :aria-label="'Kemasan barang ' + (index + 1)" type="number" x-model="row.sold_sacks" min="1" max="1000000" step="1" required class="{{ $input }}"><span class="text-xs text-error-500" x-text="error(index,'sold_sacks')"></span></td>
<td class="min-w-32 p-2"><input :aria-label="'Harga barang ' + (index + 1)" type="number" x-model="row.price_per_kg" min="0" max="999999999.99" step="0.01" required class="{{ $input }}"><span class="text-xs text-error-500" x-text="error(index,'price_per_kg')"></span></td>
<td class="whitespace-nowrap p-2 pt-5 text-right tabular-nums" x-text="formatCurrency(Number(row.sold_weight_kg || 0) * Number(row.price_per_kg || 0))"></td>
<td class="p-2 pt-5"><button type="button" @click="form.data.items.splice(index,1)" class="text-red-600" :aria-label="'Hapus barang ' + (index + 1)">Hapus</button></td>
</tr></template></tbody></table></div>
<p x-show="!form.data.items.length" class="py-5 text-center text-sm text-gray-500">Pilih PO atau tambahkan barang.</p>
<div class="mt-5 border-t pt-4 dark:border-gray-700"><h4 class="mb-3 text-sm font-semibold">Bonus &amp; Potongan Seluruh Transaksi</h4>
<div class="grid gap-4 sm:grid-cols-3">
<label class="text-sm">Bonus / kemasan (Kg)<input type="number" x-model="form.data.deduction_weight_kg" min="0" max="1000" step="0.01" required class="{{ $input }}"><span class="text-xs text-error-500" x-text="form.getError('deduction_weight_kg')"></span></label>
<label class="text-sm">Jenis potongan<select x-model="form.data.deduction_type" @change="form.data.deduction_value = 0" class="{{ $input }}"><option value="none">Tanpa potongan</option><option value="percentage">Persentase (%)</option><option value="nominal">Nominal (Rp)</option><option value="weight">Berat total (Kg)</option></select></label>
<label class="text-sm">Nilai potongan<input type="number" x-model="form.data.deduction_value" :disabled="form.data.deduction_type === 'none'" min="0" :max="form.data.deduction_type === 'percentage' ? 100 : 999999999.99" step="0.01" required class="{{ $input }}"><span class="text-xs text-error-500" x-text="form.getError('deduction_value')"></span></label>
</div><p class="mt-3 text-xs text-gray-500">Potongan dihitung satu kali dari total transaksi. Bonus per kemasan berlaku untuk seluruh barang.</p></div>
</section></div>
<aside class="space-y-4 rounded-xl border bg-white p-5 xl:sticky xl:top-5 dark:border-gray-800 dark:bg-gray-900">
<h3 class="font-semibold">Ringkasan Penjualan</h3>
<div class="space-y-3 text-sm"><div class="flex justify-between"><span>Jumlah barang</span><span x-text="form.data.items.length"></span></div><div class="flex justify-between"><span>Berat terjual</span><span x-text="formatWeight(totalWeight)"></span></div><div class="flex justify-between"><span>Jumlah kemasan</span><span x-text="totalPackages"></span></div><div class="flex justify-between"><span>Nilai barang</span><span class="tabular-nums" x-text="formatCurrency(grossTotal)"></span></div><div class="flex justify-between"><span>Bonus berat</span><span class="tabular-nums" x-text="formatCurrency(grossTotal - baseTotal)"></span></div><div class="flex justify-between"><span>Potongan transaksi</span><span class="tabular-nums" x-text="formatCurrency(discount)"></span></div></div>
<div class="border-y py-4 dark:border-gray-700"><p class="text-sm text-gray-500">Total Penjualan</p><p class="mt-2 text-3xl font-semibold tabular-nums text-brand-600" x-text="formatCurrency(netTotal)"></p></div>
<p class="text-xs leading-5 text-gray-500">Stok belum berkurang saat penjualan disimpan. Pilih stok yang tersedia saat membuat Surat Jalan.</p>
<p role="alert" class="text-sm text-error-500" x-text="form.getError('items') || serverError"></p>
<button type="submit" :disabled="form.processing || !form.data.items.length" class="w-full rounded-lg bg-brand-600 px-5 py-3 font-semibold text-white disabled:opacity-50" x-text="form.processing ? 'Menyimpan...' : 'Simpan Penjualan'"></button>
</aside></fieldset></form>
@endsection
@push('scripts')
<script>
function salesCheckout() {
return {
purchaseOrders: @js($purchaseOrders), nextKey: 0, serverError: '',
form: useForm({sale_source: 'direct', customer_purchase_order_id: null, factory_id: '', sale_date: window.getLocalDateInputValue(), deduction_type: 'none', deduction_value: 0, deduction_weight_kg: 0, items: []}),
init() { this.addItem(); },
newItem() { return {key: ++this.nextKey, item_name: 'Rumput Laut', customer_purchase_order_item_id: null, sold_weight_kg: '', sold_sacks: '', price_per_kg: '', po_remaining: null}; },
addItem() { this.form.data.items.push(this.newItem()); },
changeSource() { this.form.data.customer_purchase_order_id = null; this.form.data.factory_id = ''; this.form.data.items = []; this.form.clearErrors(); this.serverError = ''; if (this.form.data.sale_source === 'direct') this.addItem(); },
selectOrder() {
const order = this.purchaseOrders.find(po => po.id == this.form.data.customer_purchase_order_id);
this.form.data.factory_id = order?.factory_id || '';
this.form.data.items = order ? (order.items.length ? order.items : [{id: null, description: order.item_name, price_per_kg: order.price_per_kg, remaining_kg: order.remaining_kg}]).map(item => ({...this.newItem(), item_name: item.description, customer_purchase_order_item_id: item.id, sold_weight_kg: item.remaining_kg, price_per_kg: item.price_per_kg, po_remaining: item.remaining_kg})) : [];
this.form.clearErrors(); this.serverError = '';
},
error(index, field) { return this.form.getError('items.' + index + '.' + field); },
get grossTotal() { return this.form.data.items.reduce((sum,row) => sum + Number(row.sold_weight_kg || 0) * Number(row.price_per_kg || 0), 0); },
get baseTotal() { return this.form.data.items.reduce((sum,row) => sum + Math.round(Math.max(0, Number(row.sold_weight_kg || 0) - Number(row.sold_sacks || 0) * Number(this.form.data.deduction_weight_kg || 0)) * Number(row.price_per_kg || 0)), 0); },
get discount() {
const value = Number(this.form.data.deduction_value || 0), type = this.form.data.deduction_type;
return type === 'percentage' ? Math.round(this.baseTotal * value / 100) : type === 'nominal' ? Math.round(value) : type === 'weight' && this.totalWeight > 0 ? Math.round(this.baseTotal * value / this.totalWeight) : 0;
},
get netTotal() { return Math.max(0, this.baseTotal - this.discount); },
get totalWeight() { return this.form.data.items.reduce((sum,row) => sum + Number(row.sold_weight_kg || 0), 0); },
get totalPackages() { return this.form.data.items.reduce((sum,row) => sum + Number(row.sold_sacks || 0), 0); },
async submit() {
if (this.form.processing) return;
this.serverError = '';
await this.form.submit('post', @js(route('manajemen-penjualan.store')), {
transform: data => ({...data, items: data.items.map(({key, po_remaining, ...row}) => row)}),
onSuccess: () => { window.location.href = @js(route('manajemen-penjualan.index')); },
onError: error => { if (!this.form.hasErrors()) this.serverError = error.response?.data?.message || 'Penjualan belum tersimpan. Silakan coba lagi.'; }
});
}
};
}
</script>
@endpush
