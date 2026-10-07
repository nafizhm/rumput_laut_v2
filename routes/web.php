<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CashFlow\CashFlowController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Finance\ChartOfAccountController;
use App\Http\Controllers\Finance\FinancialReportController;
use App\Http\Controllers\Finance\JournalController;
use App\Http\Controllers\Master\ExpenseCategoryController;
use App\Http\Controllers\Master\FactoryController;
use App\Http\Controllers\Master\FarmerController;
use App\Http\Controllers\Master\InventoryController;
use App\Http\Controllers\Master\QualityGradeController;
use App\Http\Controllers\Master\WarehouseAreaController;
use App\Http\Controllers\Sales\CustomerPurchaseOrderController;
use App\Http\Controllers\Sales\DeliveryNoteController;
use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Setting\MenuController;
use App\Http\Controllers\Setting\PermissionController;
use App\Http\Controllers\Setting\RoleController;
use App\Http\Controllers\Transaction\FarmerPaymentController;
use App\Http\Controllers\Transaction\GoodsReceiptController;
use App\Http\Controllers\Transaction\HarvestTransactionController;
use App\Http\Controllers\Transaction\LoanController;
use App\Http\Controllers\Transaction\ProductionController;
use App\Http\Controllers\Transaction\SalesController;
use App\Http\Controllers\Transaction\TransactionController;
use App\Http\Controllers\Transaction\WarehouseController;
use App\Http\Controllers\Transaction\WarehouseDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');

    Route::resource('manajemen-menu', MenuController::class);
    Route::get('menu-data', [MenuController::class, 'data'])->name('menu-data');

    Route::resource('manajemen-permission', PermissionController::class);
    Route::get('permission-data', [PermissionController::class, 'data'])->name('permission-data');

    Route::resource('manajemen-role', RoleController::class);
    Route::get('role-data', [RoleController::class, 'data'])->name('role-data');

    Route::resource('manajemen-petani', FarmerController::class);
    Route::get('kualitas-barang', [QualityGradeController::class, 'index'])->name('kualitas-barang.index')->middleware('can:quality.read');
    Route::get('kualitas-barang/create', [QualityGradeController::class, 'create'])->name('kualitas-barang.create')->middleware('can:quality.create');
    Route::post('kualitas-barang', [QualityGradeController::class, 'store'])->name('kualitas-barang.store')->middleware('can:quality.create');
    Route::get('kualitas-barang/{kualitas_barang}/edit', [QualityGradeController::class, 'edit'])->name('kualitas-barang.edit')->middleware('can:quality.update');
    Route::put('kualitas-barang/{kualitas_barang}', [QualityGradeController::class, 'update'])->name('kualitas-barang.update')->middleware('can:quality.update');
    Route::get('farmer-data', [FarmerController::class, 'data'])->name('farmer-data');
    Route::get('manajemen-petani/{id}/ledgers', [FarmerController::class, 'ledgers'])->name('farmer.ledgers');
    Route::post('manajemen-petani/pay', [FarmerPaymentController::class, 'store'])->name('farmer.pay');
    Route::delete('manajemen-petani/pay/{farmer_payment}', [FarmerPaymentController::class, 'destroy'])->name('farmer.pay.destroy');

    Route::resource('manajemen-kategori-pengeluaran', ExpenseCategoryController::class);
    Route::get('expense-category-data', [ExpenseCategoryController::class, 'data'])->name('expense-category-data');

    Route::resource('manajemen-pabrik', FactoryController::class);
    Route::get('factory-data', [FactoryController::class, 'data'])->name('manajemen-pabrik.data');

    Route::resource('manajemen-inventori', InventoryController::class);
    Route::get('inventory-data', [InventoryController::class, 'data'])->name('manajemen-inventori.data');

    Route::resource('manajemen-pinjaman', LoanController::class);
    Route::get('loan-data', [LoanController::class, 'data'])->name('loan-data');

    Route::resource('manajemen-panen', HarvestTransactionController::class);
    Route::get('harvest-data', [HarvestTransactionController::class, 'data'])->name('harvest-data');

    Route::resource('manajemen-pemasukan-pengeluaran', TransactionController::class);
    Route::get('transaction-data', [TransactionController::class, 'data'])->name('transaction-data');

    // Warehouse
    Route::get('produksi/create', [ProductionController::class, 'create'])->name('produksi.create')->middleware('can:production.create');
    Route::post('produksi', [ProductionController::class, 'store'])->name('produksi.store')->middleware('can:production.create');
    Route::get('produksi/{produksi}', [ProductionController::class, 'show'])->name('produksi.show')->middleware('can:production.read');
    Route::post('produksi/{produksi}/complete', [ProductionController::class, 'complete'])->name('produksi.complete')->middleware('can:production.confirm');
    Route::post('produksi/{produksi}/pack', [ProductionController::class, 'pack'])->name('produksi.pack')->middleware('can:production.confirm');
    Route::post('penerimaan-barang/{penerimaan_barang}/link-purchase', [GoodsReceiptController::class, 'linkPurchase'])->name('penerimaan-barang.link-purchase')->middleware('can:receiving.update');
    Route::resource('area-gudang', WarehouseAreaController::class)->only(['index', 'create', 'store', 'edit', 'update'])->middlewareFor(['index'], 'can:area.read')->middlewareFor(['create', 'store'], 'can:area.create')->middlewareFor(['edit', 'update'], 'can:area.update');
    Route::resource('penerimaan-barang', GoodsReceiptController::class)
        ->parameters(['penerimaan-barang' => 'penerimaan_barang'])
        ->middlewareFor(['index', 'show'], 'can:receiving.read')->middlewareFor(['create', 'store'], 'can:receiving.create')
        ->middlewareFor(['edit', 'update'], 'can:receiving.update')->middlewareFor(['destroy'], 'can:receiving.delete');
    Route::post('penerimaan-barang/{penerimaan_barang}/confirm', [GoodsReceiptController::class, 'confirm'])->name('penerimaan-barang.confirm')->middleware('can:receiving.confirm');
    Route::get('manajemen-gudang/tahap/{stage}', [WarehouseDashboardController::class, 'stage'])->name('manajemen-gudang.stage')->middleware('can:receiving.read');
    Route::get('manajemen-gudang/area/{area}', [WarehouseDashboardController::class, 'area'])->name('manajemen-gudang.area')->middleware('can:receiving.read');
    Route::get('manajemen-gudang/mutasi', [WarehouseController::class, 'movements'])->name('manajemen-gudang.movements');
    Route::get('warehouse-stock', [WarehouseController::class, 'stockData'])->name('manajemen-gudang.stock');
    Route::get('manajemen-gudang', [WarehouseController::class, 'index'])->name('manajemen-gudang.index');
    Route::get('manajemen-gudang/basah', [WarehouseController::class, 'wet'])->name('manajemen-gudang.wet');
    Route::get('manajemen-gudang/kering', [WarehouseController::class, 'dry'])->name('manajemen-gudang.dry');
    Route::get('manajemen-gudang/inventori/{inventory}', [WarehouseController::class, 'inventory'])->name('manajemen-gudang.inventory');
    Route::get('warehouse-data', [WarehouseController::class, 'data'])->name('manajemen-gudang.data');
    Route::post('manajemen-gudang/receive', [WarehouseController::class, 'receive'])->name('manajemen-gudang.receive');
    Route::post('manajemen-gudang/shrinkage', [WarehouseController::class, 'shrinkage'])->name('manajemen-gudang.shrinkage');
    Route::post('manajemen-gudang/transfer', [WarehouseController::class, 'transfer'])->name('manajemen-gudang.transfer');
    Route::post('manajemen-gudang/adjustment', [WarehouseController::class, 'adjustment'])->name('manajemen-gudang.adjustment');

    // Sales
    Route::post('invoice/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoice.pay');
    Route::get('invoice-data', [InvoiceController::class, 'data'])->name('invoice.data');
    Route::get('invoice/{invoice}/cetak', [InvoiceController::class, 'print'])->name('invoice.print');
    Route::resource('invoice', InvoiceController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('surat-jalan-data', [DeliveryNoteController::class, 'data'])->name('surat-jalan.data');
    Route::post('surat-jalan/{surat_jalan}/dispatch', [DeliveryNoteController::class, 'dispatch'])->name('surat-jalan.dispatch')->middleware('can:manajemen-penjualan.create');
    Route::get('surat-jalan/{surat_jalan}/cetak', [DeliveryNoteController::class, 'print'])->name('surat-jalan.print');
    Route::resource('surat-jalan', DeliveryNoteController::class)
        ->parameters(['surat-jalan' => 'surat_jalan'])->only(['index', 'store', 'update', 'destroy']);
    Route::get('po-konsumen/{po_konsumen}/file', [CustomerPurchaseOrderController::class, 'file'])->name('po-konsumen.file');
    Route::get('po-konsumen-data', [CustomerPurchaseOrderController::class, 'data'])->name('po-konsumen.data');
    Route::resource('po-konsumen', CustomerPurchaseOrderController::class)
        ->parameters(['po-konsumen' => 'po_konsumen'])
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('manajemen-penjualan', SalesController::class);
    Route::get('sales-data', [SalesController::class, 'data'])->name('manajemen-penjualan.data');
    Route::get('finished-batches', [SalesController::class, 'finishedBatches'])->name('manajemen-penjualan.batches');
    Route::post('manajemen-penjualan/{manajemen_penjualan}/pay', [SalesController::class, 'pay'])->name('manajemen-penjualan.pay');

    // Cash Flow
    Route::prefix('keuangan')->group(function () {
        Route::post('mulai-saldo-nol', [JournalController::class, 'startZero'])->name('saldo-awal.start-zero')->middleware('can:journal.post');
        Route::get('saldo-awal', [JournalController::class, 'opening'])->name('saldo-awal.index')->middleware('can:journal.read');
        Route::get('saldo-awal/create', [JournalController::class, 'create'])->name('saldo-awal.create')->middleware('can:journal.create');
        Route::post('saldo-awal', [JournalController::class, 'store'])->name('saldo-awal.store')->middleware('can:journal.create');
        Route::resource('jurnal', JournalController::class)
            ->parameters(['jurnal' => 'jurnal'])
            ->middlewareFor(['index', 'show'], 'can:journal.read')->middlewareFor(['create', 'store'], 'can:journal.create')
            ->middlewareFor(['edit', 'update'], 'can:journal.update')->middlewareFor(['destroy'], 'can:journal.delete');
        Route::post('jurnal/{jurnal}/post', [JournalController::class, 'post'])->name('jurnal.post')->middleware('can:journal.post');
        Route::post('jurnal/{jurnal}/reverse', [JournalController::class, 'reverse'])->name('jurnal.reverse')->middleware('can:journal.reverse');
        Route::get('neraca', [FinancialReportController::class, 'balanceSheet'])->name('neraca.index')->middleware('can:financial-reports.read');
        Route::get('laba-rugi', [FinancialReportController::class, 'profitLoss'])->name('laba-rugi.index')->middleware('can:financial-reports.read');
        Route::get('coa', [ChartOfAccountController::class, 'index'])->name('coa.index')->middleware('can:coa.read');
        Route::get('coa/create', [ChartOfAccountController::class, 'create'])->name('coa.create')->middleware('can:coa.create');
        Route::post('coa', [ChartOfAccountController::class, 'store'])->name('coa.store')->middleware('can:coa.create');
        Route::get('coa/{coa}/edit', [ChartOfAccountController::class, 'edit'])->name('coa.edit')->middleware('can:coa.update');
        Route::put('coa/{coa}', [ChartOfAccountController::class, 'update'])->name('coa.update')->middleware('can:coa.update');
    });

    Route::get('laporan-arus-kas', [CashFlowController::class, 'index'])->name('cash-flow.index');
    Route::get('laporan-arus-kas/data', [CashFlowController::class, 'data'])->name('cash-flow.data');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/test', function () {
    return view('test');
})->name('test.index');
