<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #edf1f5; color: #233443; font: 13px/1.6 Arial, sans-serif; }
        .toolbar { max-width: 900px; margin: 20px auto; display: flex; justify-content: space-between; align-items: center; padding: 0 16px; gap: 16px; }
        .toolbar button, .toolbar a { border: 0; border-radius: 8px; padding: 10px 20px; background: #18334e; color: white; text-decoration: none; cursor: pointer; font: inherit; }
        .sheet { width: 210mm; max-width: 100%; margin: 0 auto 30px; background: white; padding: 12mm; box-shadow: 0 4px 24px #18334e15; }
        header { display: flex; align-items: center; justify-content: space-between; gap: 20px; border-bottom: 4px solid #18334e; padding-bottom: 16px; }
        .logo { width: 220px; max-width: 45%; height: auto; }
        .heading { text-align: right; }
        h1 { margin: 0; color: #18334e; font-size: 24px; letter-spacing: 1px; }
        .company { font-size: 11px; font-weight: bold; }
        .bar { background: #18334e; color: white; font-weight: bold; padding: 10px 14px; margin-top: 18px; letter-spacing: .6px; }
        .meta { margin: 18px 0 24px; width: 100%; border-collapse: collapse; }
        .meta th { width: 145px; text-align: left; color: #71808c; vertical-align: top; padding: 3px 0; }
        .meta td { padding: 3px 0; overflow-wrap: anywhere; white-space: pre-line; }
        .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items th { background: #edf5f8; font-size: 10px; text-align: left; text-transform: uppercase; padding: 10px; border-top: 1px solid #b8c9d1; border-bottom: 1px solid #b8c9d1; }
        .items td { padding: 16px 10px; border-bottom: 1px solid #d8e1e7; vertical-align: top; overflow-wrap: anywhere; white-space: pre-line; }
        .text-right { text-align: right; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
        .items th:nth-child(n+3) { text-align: right; }
        .items tr { break-inside: avoid; }
        .notes { margin-top: 24px; border: 1px solid #eec567; border-radius: 8px; background: #fff5da; padding: 14px; overflow-wrap: anywhere; }
        .notes strong { color: #c57a13; font-size: 12px; }
        .notes p { margin: 4px 0 0; white-space: pre-line; }
        @page { size: A4; margin: 12mm; }
        @media print {
            body { background: white; }
            .toolbar { display: none; }
            .sheet { width: 100%; max-width: none; padding: 0; margin: 0; box-shadow: none; }
            * { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            thead { display: table-header-group; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('invoice.index') }}">Kembali</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
    <main class="sheet">
        <header>
            <img class="logo" src="{{ asset('images/branding/albagro.jpeg') }}" alt="Logo PT Albagro Indo Sejahtera">
            <div class="heading"><h1>INVOICE</h1><div class="company">PT ALBAGRO INDO SEJAHTERA</div></div>
        </header>
        <div class="bar">INVOICE CUSTOMER</div>
        <table class="meta">
            <tr><th>No. Invoice</th><td>{{ $invoice->number }}</td></tr>
            @if($invoice->sales_transaction_id)<tr><th>No. Penjualan</th><td>{{ $invoice->sale->invoice_number }}</td></tr>@endif
            @if($invoice->po_number)<tr><th>No. PO</th><td>{{ $invoice->po_number }}</td></tr>@endif
            @if($invoice->delivery_note_references)
                <tr><th>No. Surat Jalan</th><td>@foreach($invoice->delivery_note_references as $reference)<div>{{ $reference['number'] }} ({{ $reference['date'] }})</div>@endforeach</td></tr>
            @elseif($invoice->delivery_note_number)
                <tr><th>No. Surat Jalan</th><td>{{ $invoice->delivery_note_number }}</td></tr>
            @endif
            <tr><th>Tanggal</th><td>{{ $invoice->invoice_date->format('d/m/Y') }}</td></tr>
            <tr><th>Kepada</th><td>{{ $invoice->customer_name }}</td></tr>
            <tr><th>Alamat</th><td>{{ $invoice->customer_address }}</td></tr>
            <tr><th>Jatuh Tempo</th><td>{{ $invoice->due_date->format('d/m/Y') }} (termin {{ $invoice->term_days }} hari)</td></tr>
            <tr><th>Status Pembayaran</th><td>{{ $invoice->payment_status === 'paid' ? 'Sudah lunas' : 'Belum lunas' }}@if($invoice->payment_status !== 'paid') &middot; {{ $invoice->due_label }}@endif</td></tr>
        </table>
        <table class="items">
            <colgroup><col style="width:40%"><col style="width:18%"><col style="width:20%"><col style="width:22%"></colgroup>
            <thead><tr><th>Deskripsi</th><th>Qty</th><th>Harga</th><th>Jumlah</th></tr></thead>
            <tbody><tr>
                <td>{{ $invoice->item_name }}</td>
                <td>{{ number_format((float) $invoice->quantity_kg, 2, ',', '.') }} kg</td>
                <td class="text-right tabular-nums">Rp{{ number_format((float) $invoice->price_per_kg, 2, ',', '.') }}</td>
                <td class="text-right tabular-nums">Rp{{ number_format((float) $invoice->quantity_kg * (float) $invoice->price_per_kg, 2, ',', '.') }}</td>
            </tr></tbody>
        </table>
        <div class="notes">
            @if($invoice->deduction_amount > 0)<p>Potongan penjualan: Rp{{ number_format($invoice->deduction_amount, 2, ',', '.') }}</p>@endif
            <strong>TOTAL TAGIHAN: Rp{{ number_format($invoice->total_amount, 2, ',', '.') }}</strong>
            @if($invoice->payment_terms)<p>Pembayaran: {{ $invoice->payment_terms }}</p>@endif
            @if($invoice->notes)<p>{{ $invoice->notes }}</p>@endif
        </div>
    </main>
</body>
</html>
