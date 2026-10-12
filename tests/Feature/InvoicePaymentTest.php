<?php

use App\Models\CustomerPurchaseOrder;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

beforeEach(function () {
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_01_01_130309_create_permission_tables.php',
        '2026_01_01_130425_create_menus_table.php',
        '2026_10_06_000001_create_customer_purchase_orders_table.php',
        '2026_10_06_000004_create_delivery_notes_table.php',
        '2026_10_06_000006_create_invoices_table.php',
        '2026_10_06_225852_create_invoice_payments_table.php',
        '2026_10_06_000008_add_po_reference_to_invoices.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }

    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'Tester', 'email' => 'payment@example.test', 'password' => 'test-password']));
    $this->grantModuleAccess(auth()->user(), ['invoice']);
    Carbon::setTestNow(Carbon::parse('2026-10-06 00:05:00', 'Asia/Jakarta'));

    $this->po = CustomerPurchaseOrder::create([
        'po_number' => 'PO-2026-101', 'po_date' => '2026-09-29', 'customer_name' => 'PT Maju Jaya',
        'customer_address' => 'Gudang Customer / Makassar', 'item_name' => 'Gracilaria Kering',
        'quantity_kg' => '2000', 'price_per_kg' => '13000',
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('records partial invoice payments without marking paid before the balance is cleared', function () {
    $invoice = Invoice::create([
        'customer_purchase_order_id' => $this->po->id,
        'number' => 'INV-2026-0201', 'invoice_date' => '2026-09-29', 'due_date' => '2026-10-29',
        'customer_name' => 'PT Maju Jaya', 'customer_address' => 'Gudang Customer / Makassar',
        'item_name' => 'Gracilaria Kering', 'quantity_kg' => '2000', 'price_per_kg' => '13000',
        'payment_status' => 'unpaid', 'payment_terms' => 'Transfer', 'notes' => 'Catatan invoice',
    ]);

    $payload = [
        'invoice_id' => $invoice->id,
        'payment_date' => '2026-10-06',
        'amount' => 10000000,
        'notes' => 'Cicilan pertama',
    ];

    $this->postJson(route('invoice.pay', $invoice), $payload)->assertOk();
    $invoice->refresh();

    expect(InvoicePayment::count())->toBe(1)
        ->and($invoice->paid_amount)->toBe(10000000)
        ->and($invoice->remaining_amount)->toBe(16000000)
        ->and($invoice->payment_status)->toBe('partial')
        ->and($invoice->payment_status_label)->toBe('Cicilan');
});

it('marks invoices as paid only when the remaining balance is fully covered', function () {
    $invoice = Invoice::create([
        'customer_purchase_order_id' => $this->po->id,
        'number' => 'INV-2026-0202', 'invoice_date' => '2026-09-29', 'due_date' => '2026-10-29',
        'customer_name' => 'PT Maju Jaya', 'customer_address' => 'Gudang Customer / Makassar',
        'item_name' => 'Gracilaria Kering', 'quantity_kg' => '2000', 'price_per_kg' => '13000',
        'payment_status' => 'unpaid', 'payment_terms' => 'Transfer', 'notes' => 'Catatan invoice',
    ]);

    $this->postJson(route('invoice.pay', $invoice), [
        'invoice_id' => $invoice->id,
        'payment_date' => '2026-10-06',
        'amount' => 10000000,
        'notes' => 'Cicilan pertama',
    ])->assertOk();

    $this->postJson(route('invoice.pay', $invoice), [
        'invoice_id' => $invoice->id,
        'payment_date' => '2026-10-07',
        'amount' => 16000000,
        'notes' => 'Pelunasan',
    ])->assertOk();

    $invoice->refresh();

    expect(InvoicePayment::count())->toBe(2)
        ->and($invoice->paid_amount)->toBe(26000000)
        ->and($invoice->remaining_amount)->toBe(0)
        ->and($invoice->payment_status)->toBe('paid')
        ->and($invoice->payment_status_label)->toBe('Sudah lunas');
});

it('prevents payments on settled invoices and rejects amounts beyond the remaining balance', function () {
    $invoice = Invoice::create([
        'customer_purchase_order_id' => $this->po->id,
        'number' => 'INV-2026-0203', 'invoice_date' => '2026-09-29', 'due_date' => '2026-10-29',
        'customer_name' => 'PT Maju Jaya', 'customer_address' => 'Gudang Customer / Makassar',
        'item_name' => 'Gracilaria Kering', 'quantity_kg' => '2000', 'price_per_kg' => '13000',
        'payment_status' => 'paid', 'payment_terms' => 'Transfer', 'notes' => 'Catatan invoice',
    ]);

    $this->postJson(route('invoice.pay', $invoice), [
        'invoice_id' => $invoice->id,
        'payment_date' => '2026-10-06',
        'amount' => 1,
    ])->assertUnprocessable()->assertJsonPath('message', 'Invoice ini sudah lunas.');

    $partialInvoice = Invoice::create([
        'customer_purchase_order_id' => $this->po->id,
        'number' => 'INV-2026-0204', 'invoice_date' => '2026-09-29', 'due_date' => '2026-10-29',
        'customer_name' => 'PT Maju Jaya', 'customer_address' => 'Gudang Customer / Makassar',
        'item_name' => 'Gracilaria Kering', 'quantity_kg' => '2000', 'price_per_kg' => '13000',
        'paid_amount' => 10000000, 'payment_status' => 'partial',
        'payment_terms' => 'Transfer', 'notes' => 'Catatan invoice',
    ]);

    $this->postJson(route('invoice.pay', $partialInvoice), [
        'invoice_id' => $partialInvoice->id,
        'payment_date' => '2026-10-06',
        'amount' => 20000000,
    ])->assertUnprocessable()->assertJsonPath('message', 'Jumlah pembayaran tidak boleh melebihi sisa tagihan sebesar Rp 16.000.000.');
});
