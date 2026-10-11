<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->number }}</title>
    @include('pages.sales.partials.document-style')
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('invoice.index') }}">Kembali</a>
        <span>{{ $invoice->payment_status_label }}</span>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
    <main class="sheet invoice">
        @include('pages.sales.partials.document-header', [
            'documentTitle' => 'INVOICE', 'documentNumber' => $invoice->number,
            'documentDate' => $invoice->invoice_date, 'dueDate' => $invoice->due_date, 'showTax' => true,
        ])
        <div class="columns">
            <section>
                <h2>DITAGIHKAN KEPADA</h2>
                <div class="field"><span class="label">Nama Pabrik</span><div class="value">{{ $invoice->customer_name }}</div></div>
                <div class="field"><span class="label">Alamat</span><div class="value">{{ $invoice->customer_address }}</div></div>
                <div class="field"><span class="label">NPWP / PIC</span><div class="value">{{ $invoice->sale?->factory?->phone }}</div></div>
            </section>
            <section>
                <h2>REFERENSI PENGIRIMAN</h2>
                <div class="field"><span class="label">No. Surat Jalan</span><div class="value">@if($invoice->delivery_note_references){{ implode(', ', array_column($invoice->delivery_note_references, 'number')) }}@else{{ $invoice->delivery_note_number }}@endif</div></div>
                <div class="field"><span class="label">Tanggal Kirim</span><div class="value">{{ implode(', ', array_unique(array_column($invoice->delivery_note_references, 'date'))) }}</div></div>
                <div class="field"><span class="label">No. PO / Kontrak (jika ada)</span><div class="value">{{ $invoice->po_number }}</div></div>
            </section>
        </div>
        <table class="items">
            <colgroup><col style="width:6%"><col style="width:39%"><col style="width:15%"><col style="width:19%"><col style="width:21%"></colgroup>
            <thead><tr><th>NO</th><th>DESKRIPSI BARANG</th><th>NETTO (KG)</th><th>HARGA / KG (RP)</th><th>JUMLAH (RP)</th></tr></thead>
            <tbody>
                <tr><td class="center">1</td><td>{{ $invoice->item_name }}</td><td class="right">{{ number_format((float) $invoice->quantity_kg, 2, ',', '.') }}</td><td class="right">Rp{{ number_format((float) $invoice->price_per_kg, 2, ',', '.') }}</td><td class="right">Rp{{ number_format((float) $invoice->quantity_kg * (float) $invoice->price_per_kg, 2, ',', '.') }}</td></tr>
                @for($row = 2; $row <= 6; $row++)<tr class="blank"><td class="center">{{ $row }}</td><td></td><td></td><td></td><td></td></tr>@endfor
            </tbody>
        </table>
        <div class="payment-summary">
            <section class="payment">
                <h2>PEMBAYARAN</h2><p>Transfer ke rekening:</p>
                <div class="field"><span class="label">Bank &amp; No. Rekening</span><div class="value">{{ trim(($profile->bank_name ?? '').' '.($profile->bank_account ?? '')) }}</div></div>
                <div class="field"><span class="label">Atas Nama</span><div class="value">{{ $profile->bank_account_name }}</div></div>
            </section>
            <table class="totals">
                <tr><td>Subtotal</td><td class="right">Rp{{ number_format((float) $invoice->quantity_kg * (float) $invoice->price_per_kg, 2, ',', '.') }}</td></tr>
                <tr><td>Potongan penjualan</td><td class="right">Rp{{ number_format($invoice->deduction_amount, 2, ',', '.') }}</td></tr>
                <tr><td>PPN (jika ada)</td><td class="right">—</td></tr>
                <tr class="grand"><td>TOTAL TAGIHAN</td><td class="right">Rp{{ number_format($invoice->total_amount, 2, ',', '.') }}</td></tr>
            </table>
        </div>
        <div class="words"><span class="label">TERBILANG</span><span># {{ \App\Support\IndonesianNumber::rupiah($invoice->total_amount) }} #</span></div>
        <div class="notes"><div class="label">CATATAN</div>
            @if($invoice->payment_terms)<p>{{ $invoice->payment_terms }}</p>@endif
            @if($invoice->notes)<p>{{ $invoice->notes }}</p>@endif
            <p>{{ $invoice->payment_status_label }}@if($invoice->payment_status !== 'paid') · {{ $invoice->due_label }}@endif · Termin {{ $invoice->term_days }} hari.</p>
            @if($invoice->sales_transaction_id)<p>No. Penjualan: {{ $invoice->sale->invoice_number }}</p>@endif
        </div>
        <div class="invoice-footer">
            <p>Mohon cantumkan nomor invoice saat pembayaran. Selisih timbang dan kadar air mengikuti berita acara penerimaan pabrik.</p>
            <div class="signature center"><h2>HORMAT KAMI</h2><div class="name">{{ $profile->signatory_name }}</div><small>Nama jelas, tanda tangan &amp; stempel</small></div>
        </div>
    </main>
</body>
</html>