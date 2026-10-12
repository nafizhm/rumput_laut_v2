<style>
    * { box-sizing: border-box; }
    :root { --ink: #143638; --green: #0b5359; --line: #87a8aa; --pale: #e7f0f0; }
    body { margin: 0; background: #edf1f3; color: var(--ink); font: 12px/1.35 'Arial Narrow', Arial, sans-serif; }
    .toolbar { width: 210mm; max-width: 100%; margin: 18px auto; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 12px; }
    .toolbar a, .toolbar button { background: var(--green); color: white; border: 0; border-radius: 6px; padding: 10px 18px; font: inherit; text-decoration: none; cursor: pointer; }
    .sheet { width: 210mm; min-height: 297mm; margin: 0 auto 24px; padding: 10mm; background: white; box-shadow: 0 2px 20px #14363815; }
    .doc-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 6mm; min-height: 32mm; padding-bottom: 4mm; border-bottom: 1.2mm solid var(--green); }
    .invoice .doc-header { min-height: 39mm; }
    .brand { display: flex; gap: 4mm; align-items: flex-start; width: 61%; }
    .delivery-note .brand, .invoice .brand { flex-direction: column; gap: 2mm; }
    .delivery-note .items { margin-top: 4mm; }
    .logo { width: 32mm; height: auto; object-fit: contain; flex: none; margin-top: 1mm; }
    .company { flex: 1; min-width: 0; overflow-wrap: anywhere; }
    .company-name { margin: 0 0 1mm; font-size: 20px; font-weight: 800; line-height: 1.15; text-transform: uppercase; }
    .company p { margin: 0; font-size: 10px; white-space: pre-line; }
    .doc-heading { width: 37%; }
    h1 { color: var(--green); margin: 0 0 3mm; font-size: 32px; line-height: 1; text-align: right; letter-spacing: 3px; font-weight: 800; }
    .doc-ref { display: flex; gap: 2mm; align-items: baseline; margin-bottom: 2mm; }
    .doc-ref .label { width: 22mm; text-align: right; flex: none; }
    .doc-ref .value { flex: 1; border-bottom: .2mm solid var(--line); padding: 0 0 1mm; overflow-wrap: anywhere; }
    .label { font-size: 9px; letter-spacing: .8px; text-transform: uppercase; }
    h2 { margin: 0 0 1.5mm; color: var(--green); font-size: 12px; letter-spacing: .5px; font-weight: 800; }
    .columns { display: grid; grid-template-columns: 1fr 1fr; gap: 5mm; margin: 4mm 0; }
    .field { margin: 0 0 1.5mm; }
    .field .label { display: block; margin-bottom: .7mm; }
    .field .value { min-height: 6mm; padding-bottom: 1mm; border-bottom: .2mm solid var(--line); white-space: pre-line; overflow-wrap: anywhere; }
    .transport { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 5mm; padding: 3mm 4mm; background: #f1f6f5; }
    .transport .field { margin: 0; }
    .categories { display: flex; flex-wrap: wrap; gap: 2mm 3mm; margin: 4mm 0; font-size: 10px; align-items: center; }
    .check { display: inline-flex; align-items: center; gap: 1.5mm; }
    .box { display: inline-flex; align-items: center; justify-content: center; width: 3.3mm; height: 3.3mm; border: .25mm solid var(--ink); font-size: 9px; font-weight: bold; }
    .categories .condition { margin-left: auto; display: flex; align-items: center; gap: 3mm; }
    .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .items th { background: var(--pale); border: .25mm solid var(--ink); font-size: 9px; line-height: 1.25; letter-spacing: .3px; padding: 2mm 1mm; text-align: center; }
    .items td { border: .2mm solid var(--line); padding: 1.5mm 1mm; height: 8mm; vertical-align: top; overflow-wrap: anywhere; }
    .items tr { break-inside: avoid; }
    .items .blank td { height: 8mm; }
    .items tfoot td { background: var(--pale); height: 8mm; font-weight: bold; vertical-align: middle; }
    .center { text-align: center; }
    .right { text-align: right; font-variant-numeric: tabular-nums; }
    .notes { border: .2mm solid var(--line); padding: 3mm; margin-top: 4mm; min-height: 55mm; white-space: normal; overflow-wrap: anywhere; }
    .notes p { margin: 2mm 0 0; white-space: pre-line; }
    .invoice .notes { min-height: 24mm; }
    .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 7mm; margin-top: 4mm; text-align: center; break-inside: avoid; }
    .signature .name { padding-top: 17mm; min-height: 23mm; border-bottom: .2mm solid var(--line); overflow-wrap: anywhere; }
    .signature small { display: block; font-size: 9px; color: #547477; margin-top: 1mm; }
    .footer { margin-top: 3mm; padding-top: 2mm; border-top: .2mm solid var(--line); display: flex; justify-content: space-between; gap: 5mm; font-size: 9px; color: #547477; }
    .payment-summary { display: grid; grid-template-columns: 1.25fr 1fr; gap: 6mm; margin-top: 4mm; break-inside: avoid; }
    .payment { padding: 3mm 4mm; background: #f1f6f5; }
    .payment p { margin: 0 0 2mm; }
    .totals { width: 100%; border-collapse: collapse; }
    .totals td { border-bottom: .2mm solid var(--line); padding: 2mm; }
    .totals .grand td { background: var(--green); color: white; font-weight: bold; font-size: 13px; border: 0; }
    .words { margin: 4mm 0; border-bottom: .2mm solid var(--line); padding-bottom: 2mm; display: flex; gap: 5mm; }
    .invoice-footer { display: grid; grid-template-columns: 1.7fr 1fr; gap: 20mm; margin-top: 4mm; align-items: end; break-inside: avoid; }
    .invoice-footer p { font-size: 9px; color: #547477; }
    .document-status { font-size: 9px; color: var(--green); margin: 2mm 0; }
    @page { size: A4 portrait; margin: 10mm; }
    @media screen and (max-width: 810px) { body { overflow-x: auto; } .sheet { margin-left: 0; } }
    @media print {
        body { background: white; }
        .toolbar { display: none; }
        .sheet { width: 190mm; min-height: 277mm; margin: 0; padding: 0; box-shadow: none; }
        * { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        thead { display: table-header-group; }
    }
</style>
