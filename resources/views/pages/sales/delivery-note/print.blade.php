<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Jalan {{ $note->number }}</title>
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
        .items tr { break-inside: avoid; }
        .notes { margin-top: 24px; border: 1px solid #eec567; border-radius: 8px; background: #fff5da; padding: 14px; overflow-wrap: anywhere; }
        .notes strong { color: #c57a13; font-size: 12px; }
        .notes p { margin: 4px 0 0; white-space: pre-line; }
        .signatures { display: flex; justify-content: space-between; gap: 35px; margin-top: 32px; break-inside: avoid; }
        .signature { width: 42%; text-align: center; }
        .signature .name { margin-top: 65px; border-bottom: 1px solid #52616d; min-height: 24px; overflow-wrap: anywhere; }
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
        <a href="{{ route('surat-jalan.index') }}">Kembali</a>
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
    <main class="sheet">
        <header>
            <img class="logo" src="{{ asset('images/branding/albagro.jpeg') }}" alt="Logo PT Albagro Indo Sejahtera">
            <div class="heading"><h1>SURAT JALAN</h1><div class="company">PT ALBAGRO INDO SEJAHTERA</div></div>
        </header>
        <div class="bar">SURAT JALAN / NOTA JALAN</div>
        <table class="meta">
            <tr><th>No. Surat Jalan</th><td>{{ $note->number }}</td></tr>
            <tr><th>Status</th><td>{{ $note->status === 'shipped' ? 'DIKIRIM' : ($note->status === 'draft' ? 'DRAFT — BELUM DIKIRIM' : 'DATA LAMA — status pengiriman belum diverifikasi') }}</td></tr>
            @if($note->shipped_date)<tr><th>Kirim Aktual</th><td>{{ $note->shipped_date->format('d/m/Y') }} — dikonfirmasi oleh {{ $note->shipped_by_name }}</td></tr>@endif
            <tr><th>Tanggal</th><td>{{ $note->delivery_date->format('d/m/Y') }}</td></tr>
            <tr><th>Kepada</th><td>{{ $note->recipient_name }}</td></tr>
            <tr><th>Alamat Tujuan</th><td>{{ $note->destination_address }}</td></tr>
            <tr><th>Asal Barang</th><td>{{ $note->origin }}</td></tr>
        </table>
        <table class="items">
            <colgroup><col style="width:6%"><col style="width:40%"><col style="width:17%"><col style="width:17%"><col style="width:20%"></colgroup>
            <thead><tr><th>No</th><th>Deskripsi Barang</th><th>Qty</th><th>Kemasan</th><th>Keterangan</th></tr></thead>
            <tbody><tr>
                <td>1</td><td>{{ $note->item_name }}</td>
                <td>{{ number_format((float) $note->quantity_kg, 2, ',', '.') }} kg</td>
                <td>{{ $note->packaging ?: '-' }}</td><td>{{ $note->remarks ?: '-' }}</td>
            </tr></tbody>
        </table>
        @if($note->notes)
            <div class="notes"><strong>CATATAN PENTING</strong><p>{{ $note->notes }}</p></div>
        @endif
        <div class="signatures">
            <div class="signature">Dikirim oleh:<div class="name">{{ $note->sender_name }}</div></div>
            <div class="signature">Diterima oleh:<div class="name">{{ $note->receiver_name }}</div></div>
        </div>
    </main>
</body>
</html>
