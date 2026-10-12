<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Jalan {{ $note->number }}</title>
    @include('pages.sales.partials.document-style')
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('surat-jalan.index') }}">Kembali</a>
        @if($note->status !== 'draft')
            <span>{{ $note->status === 'shipped' ? 'DIKIRIM' : 'DATA LAMA' }}</span>
        @endif
        <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>
    <main class="sheet delivery-note">
        @include('pages.sales.partials.document-header', [
            'documentTitle' => 'SURAT JALAN', 'documentNumber' => $note->number,
            'documentDate' => $note->delivery_date, 'showTax' => false,
        ])
        @if($note->shipped_date)<p class="document-status">Dikirim {{ $note->shipped_date->format('d/m/Y') }} · Dikonfirmasi oleh {{ $note->shipped_by_name }}</p>@endif
        <div class="columns">
            <section>
                <h2>PENGIRIM</h2>
                <div class="field"><span class="label">Nama Pengirim</span><div class="value">{{ $note->sender_name ?: '-' }}</div></div>
                <div class="field"><span class="label">Alamat</span><div class="value">{{ $note->origin_address ?: '-' }}</div></div>
                <div class="field"><span class="label">No. Telp</span><div class="value">{{ $note->sender_contact ?: '-' }}</div></div>
            </section>
            <section>
                <h2>PENERIMA (PABRIK)</h2>
                <div class="field"><span class="label">Nama Pabrik</span><div class="value">{{ $note->recipient_name }}</div></div>
                <div class="field"><span class="label">Alamat Tujuan</span><div class="value">{{ $note->destination_address }}</div></div>
                <div class="field"><span class="label">Telp / PIC Penerima</span><div class="value">{{ $note->recipient_contact ?: $note->sale?->factory?->phone }}</div></div>
            </section>
        </div>
        <div class="transport">
            <div class="field"><span class="label">No. Polisi Kendaraan</span><div class="value">{{ $note->vehicle_plate }}</div></div>
            <div class="field"><span class="label">Jenis Kendaraan</span><div class="value">{{ $note->vehicle_type }}</div></div>
            <div class="field"><span class="label">Nama Sopir</span><div class="value">{{ $note->driver_name }}</div></div>
        </div>
        <table class="items">
            <colgroup><col style="width:6%"><col style="width:28%"><col style="width:12%"><col style="width:22%"><col style="width:16%"><col style="width:16%"></colgroup>
            <thead><tr><th>NO</th><th>JENIS RUMPUT LAUT</th><th>KONDISI</th><th>JML KARUNG / BAL</th><th>BRUTO (KG)</th><th>NETTO (KG)</th></tr></thead>
            <tbody>
                @if($note->relationLoaded('items') && $note->items->isNotEmpty())
                @foreach($note->items as $item)
                <tr><td class="center">{{ $loop->iteration }}</td><td>{{ $item->item_name }}</td><td class="center">{{ $note->condition }}</td><td class="center">{{ $item->sack_count }} kemasan</td><td></td><td class="right">{{ number_format($item->quantity_kg,2,',','.') }} kg</td></tr>
                @endforeach
                @else
                <tr>
                    <td class="center">1</td><td>{{ $note->item_name }}</td><td class="center">{{ $note->condition }}</td>
                    <td class="center">{{ $note->packaging ?: ($note->sack_count ? $note->sack_count.' karung' : '') }}</td>
                    <td class="right">{{ $note->gross_weight_kg !== null ? number_format((float) $note->gross_weight_kg, 2, ',', '.') : '' }}</td>
                    
                    <td class="right">{{ number_format((float) $note->quantity_kg, 2, ',', '.') }} kg</td>
                    
                </tr>
                @for($row = 2; $row <= 6; $row++)<tr class="blank"><td class="center">{{ $row }}</td>@for($column = 0; $column < 5; $column++)<td></td>@endfor</tr>@endfor
                @endif
            </tbody>
            <tfoot><tr><td colspan="3" class="right">TOTAL</td><td class="center">{{ $note->packaging ?: ($note->sack_count ? $note->sack_count.' karung' : '') }}</td><td class="right">{{ $note->gross_weight_kg !== null ? number_format((float) $note->gross_weight_kg, 2, ',', '.') : '' }}</td><td class="right">{{ number_format((float) $note->quantity_kg, 2, ',', '.') }}</td></tr></tfoot>
        </table>
        <div class="notes">
            <div class="label">Catatan :</div>
            @if($note->remarks)<p>{{ $note->remarks }}</p>@endif
            @if($note->notes)<p>{{ $note->notes }}</p>@endif
        </div>
        <div class="signatures">
            <div class="signature"><h2>PENGIRIM</h2><div class="name">{{ $note->sender_name ?: $profile->signatory_name }}</div><small>Nama jelas &amp; tanda tangan</small></div>
            <div class="signature"><h2>SOPIR</h2><div class="name">{{ $note->driver_name }}</div><small>Nama jelas &amp; tanda tangan</small></div>
            <div class="signature"><h2>PENERIMA (PABRIK)</h2><div class="name">{{ $note->receiver_name }}</div><small>Nama jelas, tanda tangan &amp; stempel</small></div>
        </div>
        <div class="footer"><span>Barang dianggap diterima baik &amp; sesuai jumlah, kecuali ada catatan di atas.</span><span>Lembar 1: Penerima · Lembar 2: Pengirim · Lembar 3: Arsip</span></div>
    </main>
</body>
</html>
