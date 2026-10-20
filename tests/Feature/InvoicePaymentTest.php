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
        '2026_10_20_000002_add_invoice_cooperation_deductions.php',
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

function cooperationInvoice($test, string $number): Invoice
{
    return Invoice::create([
        'customer_purchase_order_id' => $test->po->id, 'number' => $number,
        'invoice_date' => '2026-09-29', 'due_date' => '2026-10-29',
        'customer_name' => 'PT Maju Jaya', 'customer_address' => 'Makassar', 'item_name' => 'Gracilaria Kering',
        'quantity_kg' => 2000, 'price_per_kg' => 13000, 'payment_status' => 'unpaid',
    ]);
}

it('settles invoices using cash plus cooperation deduction without repeating the deduction on later installments', function () {
    $invoice = cooperationInvoice($this, 'INV-KERJASAMA');
    $this->postJson(route('invoice.pay', $invoice), [
        'invoice_id' => $invoice->id, 'payment_date' => '2026-10-06', 'amount' => 9000000,
        'cooperation_deduction' => 1000000, 'cooperation_notes' => 'Bagian mitra sesuai kesepakatan',
    ])->assertOk();
    $invoice->refresh()->load('payments');
    expect((int) $invoice->paid_amount)->toBe(10000000)
        ->and($invoice->remaining_amount)->toBe(16000000)
        ->and($invoice->cash_received_amount)->toBe(9000000)
        ->and($invoice->cooperation_deduction_amount)->toBe(1000000)
        ->and($invoice->payment_status)->toBe('partial')
        ->and($invoice->payments->sole()->settlement_amount)->toBe(10000000);
    $first = $invoice->payments->sole();
    $this->postJson(route('invoice.pay', $invoice), [
        'invoice_id' => $invoice->id, 'payment_date' => '2026-10-06', 'amount' => 16000000,
    ])->assertOk();
    expect($invoice->refresh()->payment_status)->toBe('paid')
        ->and($invoice->remaining_amount)->toBe(0)
        ->and($invoice->cooperation_deduction_amount)->toBe(1000000)
        ->and($invoice->cash_received_amount)->toBe(25000000)
        ->and((int) $invoice->total_amount)->toBe(26000000);
    $this->deleteJson(route('invoice.payment.destroy', $first))->assertOk();
    expect($invoice->refresh()->payment_status)->toBe('partial')
        ->and($invoice->remaining_amount)->toBe(10000000)
        ->and($invoice->cooperation_deduction_amount)->toBe(0)
        ->and($invoice->cash_received_amount)->toBe(16000000);
});

it('rejects excessive negative or unexplained cooperation deductions and empty settlements', function () {
    $invoice = cooperationInvoice($this, 'INV-KERJASAMA-INVALID');
    $base = ['invoice_id' => $invoice->id, 'payment_date' => '2026-10-06', 'amount' => 25000000];
    $this->postJson(route('invoice.pay', $invoice), $base + ['cooperation_deduction' => -1])->assertUnprocessable();
    $this->postJson(route('invoice.pay', $invoice), $base + ['cooperation_deduction' => 1])->assertUnprocessable()->assertJsonValidationErrors('cooperation_notes');
    $this->postJson(route('invoice.pay', $invoice), $base + ['cooperation_deduction' => 2000000, 'cooperation_notes' => 'Mitra'])->assertUnprocessable();
    $this->postJson(route('invoice.pay', $invoice), array_replace($base, ['amount' => 0]))->assertUnprocessable();
    expect(InvoicePayment::count())->toBe(0)->and($invoice->refresh()->remaining_amount)->toBe(26000000);
});

it('supports a documented settlement entirely deducted for cooperation and enforces cancellation access', function () {
    $invoice = cooperationInvoice($this, 'INV-KERJASAMA-FULL');
    $this->postJson(route('invoice.pay', $invoice), [
        'invoice_id' => $invoice->id, 'payment_date' => '2026-10-06', 'amount' => 0,
        'cooperation_deduction' => 26000000, 'cooperation_notes' => 'Seluruh pencairan dialokasikan ke mitra',
    ])->assertOk();
    expect($invoice->refresh()->payment_status)->toBe('paid')->and($invoice->cash_received_amount)->toBe(0);
    auth()->user()->revokePermissionTo('invoice.update');
    $this->deleteJson(route('invoice.payment.destroy', InvoicePayment::sole()))->assertForbidden();
    expect(InvoicePayment::count())->toBe(1);
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
