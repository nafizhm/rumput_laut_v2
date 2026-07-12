<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\Factory;
use App\Models\Farmer;
use App\Models\Loan;
use App\Models\HarvestTransaction;
use App\Models\SalesTransaction;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\FarmerLedger;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DummyDataSeeder extends Seeder
{
    private array $farmerIds = [];
    private array $factoryIds = [];
    private array $inventoryIds = [];
    private array $categoryIds = [];
    private int $gudangBasahId;
    private int $gudangKeringId;

    public function run(): void
    {
        $this->command?->info('Seeding dummy data for April - May 2026...');

        $this->seedExpenseCategories();
        $this->seedFactories();
        $this->seedFarmers();
        $this->seedLoans();
        $this->seedHarvestTransactions();
        $this->seedSalesTransactions();
        $this->seedExpenses();

        $this->command?->info('Dummy data seeded successfully!');
    }

    private function seedExpenseCategories(): void
    {
        $categories = [
            ['name' => 'Transportasi'],
            ['name' => 'Konsumsi'],
            ['name' => 'Perawatan Alat'],
            ['name' => 'Gaji Karyawan'],
            ['name' => 'Listrik & Air'],
            ['name' => 'Lain-lain'],
        ];

        foreach ($categories as $cat) {
            $category = ExpenseCategory::updateOrCreate(
                ['name' => $cat['name']],
                ['is_active' => true]
            );
            $this->categoryIds[] = $category->id;
        }

        $this->command?->info('  - Expense categories created: ' . count($categories));
    }

    private function seedFactories(): void
    {
        $factories = [
            ['name' => 'Pabrik Beras Sejahtera', 'phone' => '021-5550001', 'address' => 'Jl. Raya Industri No. 10, Jakarta'],
            ['name' => 'Pabrik Beras Makmur', 'phone' => '021-5550002', 'address' => 'Jl. Merdeka No. 25, Bogor'],
            ['name' => 'Pabrik Beras Jaya Abadi', 'phone' => '021-5550003', 'address' => 'Jl. Ahmad Yani No. 50, Karawang'],
            ['name' => 'Pabrik Beras Tani Subur', 'phone' => '021-5550004', 'address' => 'Jl. Padi Indah No. 8, Subang'],
            ['name' => 'Pabrik Beras Nusantara', 'phone' => '021-5550005', 'address' => 'Jl. Gatot Subroto No. 100, Cianjur'],
        ];

        foreach ($factories as $data) {
            $factory = Factory::updateOrCreate(
                ['name' => $data['name']],
                ['phone' => $data['phone'], 'address' => $data['address']]
            );
            $this->factoryIds[] = $factory->id;
        }

        $this->command?->info('  - Factories created: ' . count($factories));
    }

    private function seedFarmers(): void
    {
        $farmers = [
            ['name' => 'Wayan Suweta', 'phone' => '081234567801', 'address' => 'Desa Cisarua, Kec. Cariu, Bogor'],
            ['name' => 'Ketut Darmawan', 'phone' => '081234567802', 'address' => 'Desa Sukamaju, Kec. Jonggol, Bogor'],
            ['name' => 'Nyoman Sudarsana', 'phone' => '081234567803', 'address' => 'Desa Cileungsi, Kec. Cileungsi, Bogor'],
            ['name' => 'Made Pasek', 'phone' => '081234567804', 'address' => 'Desa Wanasari, Kec. Cibitung, Bekasi'],
            ['name' => 'Komang Suardana', 'phone' => '081234567805', 'address' => 'Desa Mekarjaya, Kec. Taraju, Tasikmalaya'],
            ['name' => 'Asep Saepudin', 'phone' => '081234567806', 'address' => 'Desa Cikadu, Kec. Cikalong, Tasikmalaya'],
            ['name' => 'Dedi Mulyadi', 'phone' => '081234567807', 'address' => 'Desa Sukaraja, Kec. Ciawi, Bogor'],
            ['name' => 'Agus Hermawan', 'phone' => '081234567808', 'address' => 'Desa Babakan, Kec. Ciseeng, Bogor'],
            ['name' => 'Slamet Riyadi', 'phone' => '081234567809', 'address' => 'Desa Karangasem, Kec. Plered, Cirebon'],
            ['name' => 'Joko Susilo', 'phone' => '081234567810', 'address' => 'Desa Tegalwaru, Kec. Cilamaya, Karawang'],
            ['name' => 'Hendra Gunawan', 'phone' => '081234567811', 'address' => 'Desa Pasirjati, Kec. Cikalong Wetan, Bandung'],
            ['name' => 'Rudi Hartono', 'phone' => '081234567812', 'address' => 'Desa Cibogo, Kec. Lembang, Bandung'],
            ['name' => 'Bambang Supriyadi', 'phone' => '081234567813', 'address' => 'Desa Sukamandi, Kec. Ciasem, Subang'],
            ['name' => 'Eko Prasetyo', 'phone' => '081234567814', 'address' => 'Desa Rawalele, Kec. Jatibarang, Indramayu'],
            ['name' => 'Taufik Hidayat', 'phone' => '081234567815', 'address' => 'Desa Sindangpalay, Kec. Cibeureum, Sukabumi'],
        ];

        foreach ($farmers as $data) {
            $farmer = Farmer::updateOrCreate(
                ['name' => $data['name']],
                [
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'current_debt' => 0,
                ]
            );
            $this->farmerIds[] = $farmer->id;
        }

        $this->gudangBasahId = Inventory::where('item_name', 'Gudang Basah')->first()?->id
            ?? Inventory::create(['item_name' => 'Gudang Basah', 'current_stock_kg' => 0, 'current_sack_count' => 0])->id;

        $this->gudangKeringId = Inventory::where('item_name', 'Gudang Kering')->first()?->id
            ?? Inventory::create(['item_name' => 'Gudang Kering', 'current_stock_kg' => 0, 'current_sack_count' => 0])->id;

        $this->command?->info('  - Farmers created: ' . count($farmers));
    }

    private function seedLoans(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        $start = Carbon::parse('2026-04-01');
        $end = Carbon::parse('2026-05-31');
        $days = $start->diffInDays($end);
        $count = 0;

        for ($i = 0; $i < 30; $i++) {
            $farmerId = $this->farmerIds[array_rand($this->farmerIds)];
            $farmer = Farmer::find($farmerId);
            $date = $start->copy()->addDays(rand(0, $days));
            $amount = rand(5, 50) * 100000;

            $loan = Loan::create([
                'farmer_id' => $farmerId,
                'loan_date' => $date,
                'amount' => $amount,
                'description' => $faker->randomElement([
                    'Pinjaman biaya tanam',
                    'Pinjaman pupuk dan bibit',
                    'Pinjaman kebutuhan pokok',
                    'Pinjaman biaya panen',
                    'Pinjaman perbaikan sawah',
                    null,
                ]),
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $farmer->increment('current_debt', $amount);

            FarmerLedger::create([
                'farmer_id' => $farmerId,
                'reference_type' => Loan::class,
                'reference_id' => $loan->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_after' => $farmer->current_debt,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $count++;
        }

        $this->command?->info("  - Loans created: $count");
    }

    private function seedHarvestTransactions(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        $start = Carbon::parse('2026-04-01');
        $end = Carbon::parse('2026-05-31');
        $days = $start->diffInDays($end);
        $count = 0;

        for ($i = 0; $i < 40; $i++) {
            $farmerId = $this->farmerIds[array_rand($this->farmerIds)];
            $farmer = Farmer::find($farmerId);
            $date = $start->copy()->addDays(rand(0, $days));

            $totalWeight = rand(100, 1000);
            $sackCount = (int) round($totalWeight / rand(20, 30));
            $extraPerSack = rand(5, 20) / 10;
            $pricePerKg = rand(55, 80) * 100;

            $warehouseWeight = $totalWeight + ($sackCount * $extraPerSack);
            $baseValue = $totalWeight * $pricePerKg;

            $deductionTypes = ['none', 'none', 'none', 'percentage', 'nominal', 'weight'];
            $deductionType = $deductionTypes[array_rand($deductionTypes)];
            $deductionValue = 0;
            $saleDeductionAmount = 0;

            if ($deductionType === 'percentage') {
                $deductionValue = rand(1, 5);
                $saleDeductionAmount = (int) round($baseValue * ($deductionValue / 100));
            } elseif ($deductionType === 'nominal') {
                $deductionValue = rand(5, 25) * 10000;
                $saleDeductionAmount = (int) round($deductionValue);
            } elseif ($deductionType === 'weight') {
                $deductionValue = rand(1, 10);
                $saleDeductionAmount = (int) round($deductionValue * $pricePerKg);
            }

            $netHarvestValue = max(0, (int) round($baseValue) - $saleDeductionAmount);
            $cashPayout = rand(0, 1) ? min(rand(100000, $netHarvestValue), $netHarvestValue) : $netHarvestValue;
            $debtDeduction = $netHarvestValue - $cashPayout;

            $locations = ['Sawah Cikaret', 'Sawah Cimandala', 'Sawah Pasir Angin', 'Sawah Leuwi Sapi', 'Sawah Ciapus', 'Sawah Cijeruk'];
            $batchCode = 'HRV-' . $date->format('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

            $harvest = HarvestTransaction::create([
                'farmer_id' => $farmerId,
                'batch_code' => $batchCode,
                'harvest_date' => $date,
                'location' => $locations[array_rand($locations)],
                'total_weight' => $totalWeight,
                'sack_count' => $sackCount,
                'extra_per_sack' => $extraPerSack,
                'warehouse_weight' => $warehouseWeight,
                'price_per_kg' => $pricePerKg,
                'sale_deduction_type' => $deductionType,
                'sale_deduction_value' => $deductionValue,
                'sale_deduction_amount' => $saleDeductionAmount,
                'total_harvest_value' => $netHarvestValue,
                'cash_payout' => $cashPayout,
                'debt_deduction' => $debtDeduction,
                'is_received' => (bool) rand(0, 1),
                'notes' => rand(0, 1) ? $faker->sentence() : null,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            if ($debtDeduction > 0) {
                $farmer->decrement('current_debt', $debtDeduction);
                FarmerLedger::create([
                    'farmer_id' => $farmerId,
                    'reference_type' => HarvestTransaction::class,
                    'reference_id' => $harvest->id,
                    'type' => 'credit',
                    'amount' => $debtDeduction,
                    'balance_after' => $farmer->current_debt,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }

            $inventory = Inventory::find($this->gudangBasahId);
            $inventory->increment('current_stock_kg', $warehouseWeight);
            $inventory->increment('current_sack_count', $sackCount);

            StockMovement::create([
                'inventory_id' => $this->gudangBasahId,
                'harvest_transaction_id' => $harvest->id,
                'sales_transaction_id' => null,
                'movement_date' => $date,
                'direction' => 'in',
                'type' => 'in',
                'weight_kg' => $warehouseWeight,
                'note_weight_kg' => $warehouseWeight,
                'sack_count' => $sackCount,
                'balance_after_kg' => $inventory->current_stock_kg,
                'description' => "Hasil Panen: {$batchCode} - {$farmer->name}",
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $count++;
        }

        $this->command?->info("  - Harvest transactions created: $count");
    }

    private function seedSalesTransactions(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        $start = Carbon::parse('2026-04-01');
        $end = Carbon::parse('2026-05-31');
        $days = $start->diffInDays($end);
        $count = 0;

        for ($i = 0; $i < 30; $i++) {
            $inventoryId = rand(0, 1) ? $this->gudangBasahId : $this->gudangKeringId;
            $inventory = Inventory::find($inventoryId);

            if ($inventory->current_stock_kg <= 0) {
                continue;
            }

            $maxWeight = min($inventory->current_stock_kg, 500);
            if ($maxWeight < 10) {
                continue;
            }

            $factoryId = $this->factoryIds[array_rand($this->factoryIds)];
            $date = $start->copy()->addDays(rand(0, $days));

            $soldWeight = rand(10, (int) $maxWeight);
            $pricePerKg = rand(60, 100) * 100;
            $soldSacks = rand(0, 1) ? (int) round($soldWeight / rand(22, 28)) : null;

            $deductionType = rand(0, 2) ? 'none' : (rand(0, 1) ? 'percentage' : 'nominal');
            $deductionValue = 0;
            $deductionWeightKg = 0;
            $deductionAmount = 0;

            if ($deductionType === 'percentage') {
                $deductionValue = rand(1, 3);
                $deductionAmount = (int) round(($soldWeight * $pricePerKg) * ($deductionValue / 100));
            } elseif ($deductionType === 'nominal') {
                $deductionValue = rand(5, 15) * 10000;
                $deductionAmount = (int) round($deductionValue);
            } elseif ($deductionType === 'weight') {
                $deductionWeightKg = rand(1, 5);
                $baseWeight = max(0, $soldWeight - $deductionWeightKg);
                $deductionAmount = (int) round($baseWeight * $pricePerKg);
            }

            $baseWeight = max(0, $soldWeight - $deductionWeightKg);
            $totalSaleValue = max(0, (int) round($baseWeight * $pricePerKg) - $deductionAmount);

            $paymentStatus = rand(0, 1) ? 'paid' : 'unpaid';
            $paymentDate = null;
            $paidAmount = null;
            if ($paymentStatus === 'paid') {
                $paymentDate = $date->copy()->addDays(rand(1, 14));
                $paidAmount = $totalSaleValue;
            }

            $invoiceNumber = 'INV-' . $date->format('Ymd') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

            $sale = SalesTransaction::create([
                'factory_id' => $factoryId,
                'inventory_id' => $inventoryId,
                'invoice_number' => $invoiceNumber,
                'sale_date' => $date,
                'total_weight_kg' => $soldWeight,
                'price_per_kg' => $pricePerKg,
                'sold_sacks' => $soldSacks,
                'deduction_type' => $deductionType,
                'deduction_value' => $deductionValue,
                'deduction_weight_kg' => $deductionWeightKg,
                'total_sale_value' => $totalSaleValue,
                'payment_status' => $paymentStatus,
                'payment_date' => $paymentDate,
                'paid_amount' => $paidAmount,
                'payment_notes' => $paymentStatus === 'paid' ? 'Lunas' : null,
                'created_at' => $date,
                'updated_at' => $paymentDate ?? $date,
            ]);

            $inventory->decrement('current_stock_kg', $soldWeight);
            if ($soldSacks) {
                $inventory->decrement('current_sack_count', $soldSacks);
            }

            StockMovement::create([
                'inventory_id' => $inventoryId,
                'harvest_transaction_id' => null,
                'sales_transaction_id' => $sale->id,
                'movement_date' => $date,
                'direction' => 'out',
                'type' => 'out',
                'weight_kg' => $soldWeight,
                'note_weight_kg' => $soldWeight,
                'sack_count' => $soldSacks ?? 0,
                'balance_after_kg' => $inventory->current_stock_kg,
                'description' => 'Penjualan ke ' . Factory::find($factoryId)->name . ' (Invoice: ' . $invoiceNumber . ')',
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $count++;
        }

        $this->command?->info("  - Sales transactions created: $count");
    }

    private function seedExpenses(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        $start = Carbon::parse('2026-04-01');
        $end = Carbon::parse('2026-05-31');
        $days = $start->diffInDays($end);
        $count = 0;
        $descriptions = [
            'Biaya transportasi hasil panen',
            'Pembelian bahan bakar kendaraan',
            'Biaya konsumsi karyawan',
            'Perawatan mesin penggiling',
            'Pembelian suku cadang',
            'Biaya listrik pabrik',
            'Gaji karyawan lapangan',
            'Biaya administrasi',
            'Pembelian alat tulis kantor',
            'Biaya perjalanan dinas',
            null,
        ];

        for ($i = 0; $i < 20; $i++) {
            $categoryId = $this->categoryIds[array_rand($this->categoryIds)];
            $date = $start->copy()->addDays(rand(0, $days));

            Expense::create([
                'expense_category_id' => $categoryId,
                'expense_date' => $date,
                'amount' => rand(5, 200) * 10000,
                'description' => $descriptions[array_rand($descriptions)],
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            $count++;
        }

        $this->command?->info("  - Expenses created: $count");
    }
}
