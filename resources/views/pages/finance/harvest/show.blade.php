@extends('layouts.app')
@section('title', 'Pembelian & Pembayaran Panen')
@section('breadcrumb')<x-breadcrumb :pageTitle="$harvest->batch_code" :links="[['name'=>'Pembelian & Pembayaran Panen','url'=>route('harvest-finance.index')]]" />@endsection
@section('content')
@php($fieldClass = 'mt-1.5 w-full rounded-lg border border-gray-300 bg-white p-3 dark:border-gray-700 dark:bg-gray-900')
<div class="space-y-5 text-gray-800 dark:text-gray-200">
    @if(session('success'))<p role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</p>@endif
    @if($errors->any())<ul role="alert" class="rounded-lg bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
    <div class="space-y-4 rounded-xl bg-white p-6 dark:bg-gray-900">
        <div class="grid gap-3 md:grid-cols-3"><p>Panen: <strong>{{ $harvest->batch_code }}</strong></p><p>Petani: <strong>{{ $harvest->farmer->name }}</strong></p><p>Tanggal diterima: {{ $harvest->harvest_date->format('d/m/Y') }}</p><p>Berat referensi pembelian: <strong>{{ number_format($harvest->total_weight,2,',','.') }} kg</strong></p><p>Berat gudang: {{ number_format($harvest->warehouse_weight,2,',','.') }} kg / {{ $harvest->sack_count }} karung</p><p>Status: <strong>{{ $harvest->payment_status }}</strong></p></div>
        @if($harvest->receipt)<div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th class="p-2">Area</th><th class="p-2">Berat Aktual</th><th class="p-2">Karung</th></tr></thead><tbody>@foreach($harvest->receipt->lines as $line)<tr class="border-t dark:border-gray-800"><td class="p-2">{{ $line->area->name }}</td><td class="p-2">{{ number_format($line->actual_weight_kg,2,',','.') }} kg</td><td class="p-2">{{ $line->sack_count }}</td></tr>@endforeach</tbody></table></div>@endif
        <p class="whitespace-pre-line text-sm text-gray-500">{{ $harvest->notes }}</p>
    </div>

    @if(!$harvest->separate_payment)
        <div class="rounded-xl bg-white p-6 dark:bg-gray-900"><p class="text-sm">Transaksi ini menggunakan alur lama. Nilai dan pembayaran sebelumnya dipertahankan; tidak dapat dibayar ulang melalui menu ini.</p><div class="mt-3 grid gap-3 md:grid-cols-3"><p>Nilai: Rp {{ number_format($harvest->total_harvest_value,0,',','.') }}</p><p>Dibayar: Rp {{ number_format($harvest->cash_payout,0,',','.') }}</p><p>Potongan pinjaman: Rp {{ number_format($harvest->debt_deduction,0,',','.') }}</p></div></div>
    @elseif(!$harvest->priced_at)
        @if(!$harvest->is_received || $harvest->receipt?->status !== 'confirmed')
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">Menunggu konfirmasi penerimaan barang dari gudang. Nilai pembelian dapat ditetapkan setelah barang diterima dan penerimaan dikonfirmasi.</div>
        @else
        @can('harvest-finance.price')
        <form method="POST" action="{{ route('harvest-finance.price',$harvest) }}" class="space-y-4 rounded-xl bg-white p-6 dark:bg-gray-900" x-data="{ price: {{ (int) old('price_per_kg',0) }}, kind: @js(old('sale_deduction_type','none')), discount: {{ (float) old('sale_deduction_value',0) }}, weight: {{ (float) $harvest->total_weight }}, get gross() { return Math.round(this.weight * Number(this.price || 0)); }, get net() { const d = Number(this.discount || 0); return this.gross - Math.round(this.kind === 'percentage' ? this.gross * d / 100 : this.kind === 'weight' ? d * Number(this.price || 0) : this.kind === 'nominal' ? d : 0); } }">
            @csrf<h3 class="font-semibold">Tetapkan Nilai Pembelian</h3>
            <div class="grid gap-4 md:grid-cols-4">
                <label class="text-sm">Tanggal Pembelian<input name="purchase_date" type="date" min="{{ $harvest->receipt->received_date->format('Y-m-d') }}" max="{{ now('Asia/Jakarta')->format('Y-m-d') }}" value="{{ old('purchase_date',max(now('Asia/Jakarta')->format('Y-m-d'),$harvest->receipt->received_date->format('Y-m-d'))) }}" required class="{{ $fieldClass }}"></label>
                <x-input label="Harga / Kg (Rp)" name="price_per_kg" :isCurrency="true" :value="old('price_per_kg', 0)" x-model="price" required />
                <label class="text-sm">Potongan Pembelian<select name="sale_deduction_type" x-model="kind" class="{{ $fieldClass }}">@foreach(['none'=>'Tidak Ada','percentage'=>'Persentase','nominal'=>'Nominal (Rp)','weight'=>'Berat (Kg)'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                <div>
                    <template x-if="kind === 'nominal'"><div><x-input label="Nilai Potongan (Rp)" name="sale_deduction_value" :isCurrency="true" :value="old('sale_deduction_value', 0)" x-model="discount" /></div></template>
                    <template x-if="kind !== 'nominal'"><label class="block text-sm">Nilai Potongan<input name="sale_deduction_value" type="number" min="0" step="0.01" x-model="discount" :readonly="kind==='none'" class="{{ $fieldClass }}"></label></template>
                </div>
            </div>
            <p class="text-sm">Nilai bersih pembelian: <strong x-text="formatCurrency(net)"></strong></p>
            <x-info-help><p class="text-sm text-gray-500">Konfirmasi mencatat nilai persediaan dan utang kepada petani. Pembayaran dan potongan pinjaman dicatat pada langkah berikutnya. Harga dikunci setelah dikonfirmasi.</p></x-info-help>
            <label class="flex gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required>Saya sudah memeriksa harga dan nilai pembelian.</label>
            <button class="rounded-lg bg-brand-500 px-5 py-2.5 text-white">Konfirmasi Nilai Pembelian</button>
        </form>
        @endcan
        @endif
    @else
        <div class="space-y-3 rounded-xl bg-white p-6 dark:bg-gray-900">
            <h3 class="font-semibold">Ringkasan Pembelian</h3>
            <div class="grid gap-3 md:grid-cols-3">
                <p>Harga / Kg: Rp {{ number_format($harvest->price_per_kg,0,',','.') }}</p><p>Potongan pembelian: Rp {{ number_format($harvest->sale_deduction_amount,0,',','.') }}</p><p>Nilai bersih: <strong>Rp {{ number_format($harvest->total_harvest_value,0,',','.') }}</strong></p>
                <p>Total dibayar: Rp {{ number_format($harvest->cash_payout,0,',','.') }}</p><p>Total potongan pinjaman: Rp {{ number_format($harvest->debt_deduction,0,',','.') }}</p><p>Sisa utang perusahaan: <strong>Rp {{ number_format($harvest->outstanding_payable,0,',','.') }}</strong></p>
            </div>
            <p class="text-sm text-gray-500">Pembelian {{ $harvest->purchase_date->format('d/m/Y') }}, dikonfirmasi oleh {{ $harvest->priced_by_name }}.</p>
        </div>
        @if($harvest->outstanding_payable > 0)
        @can('harvest-finance.pay')
        <form method="POST" action="{{ route('harvest-finance.pay',$harvest) }}" class="space-y-4 rounded-xl bg-white p-6 dark:bg-gray-900" x-data="{ amount: {{ (int) old('amount',0) }}, deduction: {{ (int) old('debt_deduction',0) }}, outstanding: {{ $harvest->outstanding_payable }} }">
            @csrf<input type="hidden" name="submission_key" value="{{ old('submission_key',(string) Illuminate\Support\Str::uuid()) }}">
            <h3 class="font-semibold">Catat Pembayaran / Potongan Pinjaman</h3>
            <p class="text-sm">Saldo pinjaman petani: <strong>Rp {{ number_format($harvest->farmer->current_debt,0,',','.') }}</strong>.</p>
            <x-info-help>Potongan pinjaman dapat diisi tanpa pembayaran kas, atau bersama pembayaran sebagian.</x-info-help>
            <div class="grid gap-4 md:grid-cols-4">
                <label class="text-sm">Tanggal<input name="payment_date" type="date" value="{{ old('payment_date',now('Asia/Jakarta')->format('Y-m-d')) }}" required class="{{ $fieldClass }}"></label>
                <x-input label="Pembayaran (Rp)" name="amount" :isCurrency="true" :value="old('amount', 0)" x-model="amount" required />
                <x-input label="Potongan Pinjaman (Rp)" name="debt_deduction" :isCurrency="true" :value="old('debt_deduction', 0)" x-model="deduction" required />
                <label class="text-sm">Kas / Bank<select name="cash_account_id" :required="Number(amount)>0" class="{{ $fieldClass }}"><option value="">Pilih kas / bank</option>@foreach($cashAccounts as $account)<option value="{{ $account->id }}" @selected(old('cash_account_id') == $account->id)>{{ $account->code }} - {{ $account->name }}</option>@endforeach</select></label>
            </div>
            <p class="text-sm">Sisa utang setelah transaksi: <strong x-text="formatCurrency(outstanding - Number(amount || 0) - Number(deduction || 0))"></strong></p>
            <label class="block text-sm">Catatan / Referensi Pembayaran<textarea name="notes" class="{{ $fieldClass }}">{{ old('notes') }}</textarea></label>
            <label class="flex gap-3 text-sm"><input type="checkbox" name="confirm" value="1" required>Saya memastikan pembayaran dan potongan pinjaman ini benar. Konfirmasi mencatat jurnal dan tidak dapat diubah melalui form ini.</label>
            <button class="rounded-lg bg-brand-500 px-5 py-2.5 text-white">Konfirmasi Pembayaran / Potongan</button>
        </form>
        @endcan
        @endif
    @endif

    @if($harvest->payments->isNotEmpty())
    <div class="rounded-xl bg-white p-6 dark:bg-gray-900"><h3 class="mb-3 font-semibold">Riwayat Pembayaran & Potongan Pinjaman</h3><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr>@foreach(['Tanggal','Pembayaran','Potongan Pinjaman','Kas / Bank','Petugas','Catatan'] as $label)<th class="p-3">{{ $label }}</th>@endforeach</tr></thead><tbody>@foreach($harvest->payments->sortBy('id') as $payment)<tr class="border-t dark:border-gray-800"><td class="p-3">{{ $payment->payment_date->format('d/m/Y') }}</td><td class="p-3">Rp {{ number_format($payment->amount,0,',','.') }}</td><td class="p-3">Rp {{ number_format($payment->debt_deduction,0,',','.') }}</td><td class="p-3">{{ $payment->cashAccount?->name ?? '-' }}</td><td class="p-3">{{ $payment->created_by_name }}</td><td class="p-3">{{ $payment->notes }}</td></tr>@endforeach</tbody></table></div></div>
    @endif
    <a class="inline-block text-sm text-brand-500" href="{{ route('harvest-finance.index') }}">Kembali ke Pembelian & Pembayaran Panen</a>
</div>
@endsection
