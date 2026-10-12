<?php

use App\Models\DeliveryNote;
use App\Models\CustomerPurchaseOrder;
use App\Models\Invoice;
use App\Models\Menu;
use App\Models\User;
use App\Models\Factory;
use App\Models\Inventory;
use App\Models\SalesTransaction;
use Database\Seeders\CustomerPurchaseOrderMenuSeeder;
use Database\Seeders\DeliveryNoteMenuSeeder;
use Database\Seeders\InvoiceMenuSeeder;
use Illuminate\Support\Carbon;

it('uses actual invoices rather than sales for the latest invoice dashboard list', function () {
    foreach (['2026_04_26_062518_create_loans_table.php', '2026_04_26_111858_create_expense_categories_table.php', '2026_04_26_111907_create_expenses_table.php', '2026_07_13_142525_create_incomes_table.php', '2026_07_14_000002_create_transactions_table.php'] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $invoice = Invoice::create(array_merge($this->payload, ['number' => 'INV-DASHBOARD', 'invoice_date' => '2026-10-05',
        'customer_name' => 'Invoice Customer', 'quantity_kg' => 10, 'price_per_kg' => 2000, 'payment_status' => 'partial']));
    $response = app(\App\Http\Controllers\DashboardController::class)->stats(\Illuminate\Http\Request::create('/dashboard/stats'));
    $recent = $response->getData(true)['recentInvoices'];
    expect($recent)->toHaveCount(1);
    expect($recent[0]['invoice_number'])->toBe('INV-DASHBOARD');
    expect($recent[0]['invoice_date'])->toBe('05 Oct 2026');
    expect($recent[0]['customer_name'])->toBe('Invoice Customer');
    expect((float) $recent[0]['total_amount'])->toBe(20000.0);
    expect($recent[0]['payment_status'])->toBe('partial');
    expect($recent[0]['url'])->toBe(route('invoice.print', $invoice));
});

beforeEach(function () {
    foreach ([
        '0001_01_01_000000_create_users_table.php',
        '2026_01_01_130309_create_permission_tables.php',
        '2026_01_01_130425_create_menus_table.php',
        '2026_04_26_062312_create_farmers_table.php',
        '2026_04_26_062526_create_harvest_transactions_table.php',
        '2026_04_26_153147_create_inventories_table.php',
        '2026_04_26_153352_create_factories_table.php',
        '2026_04_26_153448_create_sales_transactions_table.php',
        '2026_04_26_153534_create_stock_movements_table.php',
        '2026_05_02_000003_add_payment_fields_to_sales_transactions_table.php',
        '2026_10_06_000001_create_customer_purchase_orders_table.php',
        '2026_10_06_000004_create_delivery_notes_table.php',
        '2026_10_06_000006_create_invoices_table.php',
        '2026_10_06_225852_create_invoice_payments_table.php',
        '2026_10_06_000008_add_po_reference_to_invoices.php',
        '2026_10_07_000005_link_invoices_to_sales.php',
        '2026_10_07_000006_add_delivery_confirmation_stock_flow.php',
        '2026_10_10_000002_create_company_profile_and_document_numbering.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
    $this->withoutVite();
    $this->actingAs(User::create(['name' => 'Tester', 'email' => 'invoice@example.test', 'password' => 'test-password']));
    $this->grantModuleAccess(auth()->user(), ['invoice']);
    Carbon::setTestNow(Carbon::parse('2026-10-06 00:05:00', 'Asia/Jakarta'));
    $this->po = CustomerPurchaseOrder::create([
        'po_number' => 'PO-2026-001', 'po_date' => '2026-09-29', 'customer_name' => 'PT Maju Jaya',
        'customer_address' => 'Gudang Customer / Makassar', 'item_name' => 'Gracilaria Kering',
        'quantity_kg' => '2000', 'price_per_kg' => '13000',
    ]);
    $this->payload = [
        'customer_purchase_order_id' => $this->po->id,
        'number' => 'INV-2026-0098', 'invoice_date' => '2026-09-29', 'due_date' => '2026-10-29',
        'customer_name' => 'PT Maju Jaya', 'customer_address' => 'Gudang Customer / Makassar',
        'item_name' => 'Gracilaria Kering', 'quantity_kg' => '2000', 'price_per_kg' => '13000',
        'payment_status' => 'unpaid', 'payment_terms' => 'Transfer / sesuai termin', 'notes' => 'Catatan invoice',
    ];
    $factory = Factory::create(['name' => 'PT Maju Jaya']);
    $inventory = Inventory::create(['item_name' => 'Gracilaria Kering']);
    $sale = SalesTransaction::create([
        'factory_id' => $factory->id, 'inventory_id' => $inventory->id,
        'invoice_number' => 'PJL-TEST-001', 'sale_date' => '2026-09-29',
        'total_weight_kg' => 2000, 'price_per_kg' => 13000, 'total_sale_value' => 26000000,
    ]);
    $this->payload['sales_transaction_id'] = $sale->id;
});

afterEach(function () {
    Carbon::setTestNow();
});

it('creates, lists, edits, prints and deletes invoices with calculated totals', function () {
    $this->postJson(route('invoice.store'), $this->payload + ['total_amount' => 1])->assertOk();
    $invoice = Invoice::firstOrFail();
    $this->getJson(route('invoice.data'))->assertOk()->assertJsonPath('data.0.total_amount', 26000000)
        ->assertJsonPath('data.0.term_days', 30)->assertJsonPath('data.0.days_until_due', 23);
    $this->get(route('invoice.index'))->assertOk()->assertSee('Tambah Invoice')->assertSee(route('invoice.create'));
    $this->get(route('invoice.print', $invoice))->assertOk()->assertSee('images/branding/albagro.png')
        ->assertSee('INV-2026-0098')->assertSee('PO-2026-001')->assertSee('PT Maju Jaya')->assertSee('Rp26.000.000,00')
        ->assertSee('29/10/2026')->assertSee('23 hari lagi')->assertSee('window.print()');
    $this->putJson(route('invoice.update', $invoice), array_merge($this->payload, [
        'payment_status' => 'paid', 'quantity_kg' => '1500',
    ]))->assertOk();
    $this->getJson(route('invoice.data'))->assertOk()->assertJsonPath('data.0.payment_status', 'unpaid')
        ->assertJsonPath('data.0.total_amount', 26000000);
    $this->get(route('invoice.print', $invoice))->assertOk()->assertSee('Belum lunas')->assertSee('Rp26.000.000,00');
    $this->deleteJson(route('invoice.destroy', $invoice))->assertOk();
    $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    $this->get(route('invoice.print', $invoice))->assertNotFound();
});

it('validates invoice uniqueness, date order, amount, status and reference', function () {
    $this->postJson(route('invoice.store'), $this->payload)->assertOk();
    $this->postJson(route('invoice.store'), array_merge($this->payload, [
        'due_date' => '2026-09-28', 'quantity_kg' => 0, 'price_per_kg' => -1,
        'payment_status' => 'unknown', 'customer_purchase_order_id' => 999, 'customer_name' => '',
    ]))->assertUnprocessable()->assertJsonValidationErrors([
        'number', 'due_date', 'quantity_kg', 'price_per_kg', 'payment_status', 'customer_purchase_order_id', 'customer_name',
    ]);
    $this->postJson(route('invoice.store'), array_merge($this->payload, [
        'number' => 'INV-2', 'quantity_kg' => '1.123', 'price_per_kg' => '10000000',
    ]))->assertUnprocessable()->assertJsonValidationErrors(['quantity_kg', 'price_per_kg']);
});

it('calculates due today, overdue and paid labels by Jakarta calendar dates', function () {
    $invoice = Invoice::create(array_merge($this->payload, ['due_date' => '2026-10-06']));
    expect($invoice->due_label)->toBe('Jatuh tempo hari ini');
    $invoice->update(['due_date' => '2026-10-04']);
    expect($invoice->days_until_due)->toBe(-2)->and($invoice->due_label)->toBe('Terlambat 2 hari');
    $invoice->update(['payment_status' => 'paid']);
    expect($invoice->due_label)->toBe('Sudah lunas');
    $invoice->update(['quantity_kg' => '1.25', 'price_per_kg' => '123.45']);
    expect($invoice->total_amount)->toBe(154.31);
});

it('preserves invoice details after deleting a referenced delivery note and escapes print content', function () {
    $note = DeliveryNote::create([
        'customer_purchase_order_id' => $this->po->id, 'number' => 'SJ-001', 'delivery_date' => '2026-09-29', 'recipient_name' => 'PT Maju Jaya',
        'destination_address' => 'Makassar', 'origin' => 'Sidoarjo', 'item_name' => 'Gracilaria Kering', 'quantity_kg' => 2000,
    ]);
    $this->get(route('invoice.create'))->assertOk()->assertSee('SJ-001');
    $this->postJson(route('invoice.store'), array_merge($this->payload, [
        'delivery_note_id' => $note->id, 'notes' => '<script>alert(1)</script>',
    ]))->assertOk();
    $invoice = Invoice::firstOrFail();
    $note->delete();
    expect($invoice->fresh()->delivery_note_id)->toBeNull();
    expect($invoice->fresh()->delivery_note_number)->toBe('SJ-001');
    $this->get(route('invoice.print', $invoice))->assertOk()->assertSee('PT Maju Jaya')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

it('places Invoice below Surat jalan without duplicates and requires login', function () {
    $this->seed(CustomerPurchaseOrderMenuSeeder::class);
    $this->seed(DeliveryNoteMenuSeeder::class);
    $this->seed(InvoiceMenuSeeder::class);
    $this->seed(InvoiceMenuSeeder::class);
    expect(Menu::where('name', 'Penjualan')->firstOrFail()->children->pluck('name')->all())
        ->toBe(['PO konsumen', 'Surat jalan', 'Invoice']);
    auth()->logout();
    $this->get(route('invoice.index'))->assertRedirect(route('login'));
    $this->getJson(route('invoice.data'))->assertUnauthorized();
    $this->postJson(route('invoice.store'), $this->payload)->assertUnauthorized();
    $this->putJson(route('invoice.update', 1), $this->payload)->assertUnauthorized();
    $this->deleteJson(route('invoice.destroy', 1))->assertUnauthorized();
    $this->getJson(route('invoice.print', 1))->assertUnauthorized();
});

it('automatically links the only delivery note for the selected PO', function () {
    $note = DeliveryNote::create([
        'customer_purchase_order_id' => $this->po->id, 'number' => 'SJ-AUTO', 'delivery_date' => '2026-09-29',
        'recipient_name' => 'PT Maju Jaya', 'destination_address' => 'Makassar', 'origin' => 'Sidoarjo',
        'item_name' => 'Gracilaria Kering', 'quantity_kg' => 2000,
    ]);
    $this->postJson(route('invoice.store'), $this->payload)->assertOk();
    $invoice = Invoice::firstOrFail();
    expect($invoice->delivery_note_id)->toBe($note->id)->and($invoice->po_number)->toBe('PO-2026-001');
    $this->get(route('invoice.print', $invoice))->assertOk()->assertSee('SJ-AUTO');
    $this->po->delete();
    expect($invoice->fresh()->customer_purchase_order_id)->toBeNull();
    $this->get(route('invoice.print', $invoice))->assertOk()->assertSee('PO-2026-001');
});

it('requires a PO and ignores submitted shipment references from another PO', function () {
    $payload = $this->payload;
    unset($payload['customer_purchase_order_id']);
    $this->postJson(route('invoice.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('customer_purchase_order_id');
    $note = DeliveryNote::create([
        'number' => 'SJ-UNRELATED', 'delivery_date' => '2026-09-29', 'recipient_name' => 'Other customer',
        'destination_address' => 'Other address', 'origin' => 'Sidoarjo', 'item_name' => 'Other item', 'quantity_kg' => 100,
    ]);
    $this->postJson(route('invoice.store'), $this->payload + [
        'delivery_note_id' => $note->id, 'delivery_note_references' => [['id' => $note->id, 'number' => 'FORGED']],
    ])->assertOk();
    expect(Invoice::firstOrFail()->delivery_note_references)->toBe([]);
    expect(Invoice::firstOrFail()->delivery_note_id)->toBeNull();
});

it('includes all shipments from the PO in data and print and refreshes them when changing PO', function () {
    $notes = collect(['SJ-FIRST', 'SJ-SECOND'])->map(fn ($number) => DeliveryNote::create([
        'customer_purchase_order_id' => $this->po->id, 'number' => $number, 'delivery_date' => '2026-09-29',
        'recipient_name' => 'PT Maju Jaya', 'destination_address' => 'Makassar', 'origin' => 'Sidoarjo',
        'item_name' => 'Gracilaria Kering', 'quantity_kg' => 1000,
    ]));
    $payload = $this->payload;
    $this->postJson(route('invoice.store'), $payload)->assertOk();
    $invoice = Invoice::firstOrFail();
    $this->putJson(route('invoice.update', $invoice), array_merge($payload, ['payment_status' => 'paid']))->assertOk();
    expect(array_column($invoice->fresh()->delivery_note_references, 'number'))->toBe(['SJ-FIRST', 'SJ-SECOND']);
    $this->get(route('invoice.print', $invoice))->assertOk()->assertSee('SJ-FIRST')->assertSee('SJ-SECOND');
    $this->getJson(route('invoice.data'))->assertOk()->assertJsonCount(2, 'data.0.delivery_note_references');
    $notes->last()->delete();
    $this->get(route('invoice.print', $invoice))->assertOk()->assertSee('SJ-FIRST')->assertSee('SJ-SECOND');
    $otherPo = CustomerPurchaseOrder::create([
        'po_number' => 'PO-OTHER', 'po_date' => '2026-09-29', 'customer_name' => 'PT Maju Jaya',
        'item_name' => 'Seaweed', 'quantity_kg' => 10, 'price_per_kg' => 10000,
    ]);
    $this->putJson(route('invoice.update', $invoice), array_merge($this->payload, [
        'customer_purchase_order_id' => $otherPo->id, 'delivery_note_id' => null,
    ]))->assertOk();
    expect($invoice->fresh()->delivery_note_id)->toBeNull()->and($invoice->fresh()->delivery_note_number)->toBeNull()
        ->and($invoice->fresh()->po_number)->toBe('PO-OTHER')->and($invoice->fresh()->delivery_note_references)->toBe([]);
});

it('backfills existing invoice references without changing their customer and amounts', function () {
    $migration = require database_path('migrations/2026_10_06_000008_add_po_reference_to_invoices.php');
    $migration->down();
    $note = DeliveryNote::create([
        'customer_purchase_order_id' => $this->po->id, 'number' => 'SJ-LEGACY', 'delivery_date' => '2026-09-29',
        'recipient_name' => 'PT Maju Jaya', 'destination_address' => 'Makassar', 'origin' => 'Sidoarjo',
        'item_name' => 'Seaweed', 'quantity_kg' => 2000,
    ]);
    $payload = $this->payload;
    unset($payload['customer_purchase_order_id']);
    $invoice = Invoice::create($payload + ['delivery_note_id' => $note->id]);
    $migration->up();
    $invoice->refresh();
    expect($invoice->customer_purchase_order_id)->toBe($this->po->id)
        ->and($invoice->po_number)->toBe('PO-2026-001')->and($invoice->delivery_note_number)->toBe('SJ-LEGACY')
        ->and($invoice->customer_name)->toBe('PT Maju Jaya')->and($invoice->total_amount)->toBe(26000000.0);
});

it('automatically numbers new invoices and prints the configured company and payment details', function () {
    \App\Models\CompanyProfile::whereKey(1)->update([
        'company_name' => 'PT Perusahaan Dokumen', 'address' => 'Jl. Perusahaan 10', 'phone' => '081111222333',
        'bank_name' => 'Bank Uji', 'bank_account' => '1234567890', 'bank_account_name' => 'Perusahaan Dokumen',
        'tax_number' => 'NPWP-UJI', 'signatory_name' => 'Penanggung Jawab',
    ]);
    $payload = $this->payload;
    unset($payload['number']);
    $this->postJson(route('invoice.store'), $payload)->assertOk();
    $invoice = Invoice::firstOrFail();
    expect($invoice->number)->toBe('INV/0001/09/2026');
    $this->get(route('invoice.print', $invoice))->assertOk()
        ->assertSee('PT PERUSAHAAN DOKUMEN')->assertSee('Jl. Perusahaan 10')->assertSee('081111222333')
        ->assertSee('Bank Uji')->assertSee('1234567890')->assertSee('NPWP-UJI')->assertSee('Penanggung Jawab')
        ->assertSee('Dua puluh enam juta rupiah');
    \App\Models\DocumentNumberRule::where('document_type', 'invoice')->update(['format' => 'ALB/{YYYY}/{SEQ}', 'start_number' => 50]);
    $sale = SalesTransaction::firstOrFail()->replicate();
    $sale->invoice_number = 'PJL-TEST-NEW';
    $sale->save();
    $payload['sales_transaction_id'] = $sale->id;
    $this->postJson(route('invoice.store'), $payload)->assertOk();
    expect(Invoice::latest('id')->first()->number)->toBe('ALB/2026/0050')
        ->and($invoice->refresh()->number)->toBe('INV/0001/09/2026');
});

it('groups sale products into one invoice with all items and the combined amount', function () {
    (require database_path('migrations/2026_10_12_000001_add_cart_fields_to_sales_transactions.php'))->up();
    $sale = SalesTransaction::findOrFail($this->payload['sales_transaction_id']);
    $sale->update(['sale_group_number' => 'PJL-GROUP-001', 'item_name' => 'Cottonii']);
    $second = $sale->replicate();
    $second->fill(['invoice_number' => 'PJL-TEST-002', 'item_name' => 'Spinosum',
        'total_weight_kg' => 100, 'price_per_kg' => 10000, 'total_sale_value' => 900000]);
    $second->save();
    $page = $this->get(route('invoice.create'))->assertOk()->assertSee('Barang yang Ditagihkan');
    expect($page->viewData('sales'))->toHaveCount(1);
    expect($page->viewData('sales')->first()['items'])->toHaveCount(2);
    $this->postJson(route('invoice.store'), $this->payload)->assertOk();
    $invoice = Invoice::firstOrFail();
    expect($invoice->total_amount)->toBe(26900000.0)
        ->and($invoice->document_items)->toHaveCount(2)
        ->and($invoice->deduction_amount)->toBe(100000.0);
    $this->get(route('invoice.edit', $invoice))->assertOk();
    $this->get(route('invoice.print', $invoice))->assertOk()->assertSee('Cottonii')->assertSee('Spinosum');
    $this->postJson(route('invoice.store'), array_merge($this->payload, ['number' => 'INV-DUPLICATE', 'sales_transaction_id' => $second->id]))
        ->assertUnprocessable()->assertJsonValidationErrors('sales_transaction_id');
    $this->postJson(route('invoice.pay', $invoice), ['invoice_id' => $invoice->id, 'payment_date' => '2026-10-06', 'amount' => 26900000])->assertOk();
    expect($sale->fresh()->paid_amount)->toBe(26000000)->and($second->fresh()->paid_amount)->toBe(900000);
});
