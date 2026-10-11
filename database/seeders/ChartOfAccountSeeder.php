<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $accounts = [
                ['11101', 'Kas Operasional', 'Harta', 'Kas dan Bank', 'Debit', 'Penerimaan dan pembayaran tunai untuk operasional usaha.'],
                ['11102', 'Kas Kecil', 'Harta', 'Kas dan Bank', 'Debit', 'Dana tunai untuk pengeluaran operasional kecil.'],
                ['11103', 'Bank', 'Harta', 'Kas dan Bank', 'Debit', 'Penerimaan pelunasan invoice dan pembayaran melalui rekening bank.'],
                ['11201', 'Piutang Petani', 'Harta', 'Piutang', 'Debit', 'Pinjaman yang diberikan kepada petani; berkurang saat dibayar tunai atau dipotong dari hasil panen.'],
                ['11202', 'Piutang Usaha Pabrik / Konsumen', 'Harta', 'Piutang', 'Debit', 'Tagihan penjualan kepada pabrik/konsumen yang belum dilunasi, termasuk invoice cicilan.'],
                ['11203', 'Piutang Lain-lain', 'Harta', 'Piutang', 'Debit', 'Tagihan usaha di luar pinjaman petani dan penjualan rumput laut.'],
                ['11301', 'Persediaan Rumput Laut Basah', 'Harta', 'Persediaan', 'Debit', 'Nilai perolehan panen dan stok masuk di gudang basah.'],
                ['11302', 'Persediaan Rumput Laut Kering', 'Harta', 'Persediaan', 'Debit', 'Nilai persediaan yang telah dipindahkan/diproses ke gudang kering.'],
                ['11303', 'Persediaan Karung dan Kemasan', 'Harta', 'Persediaan', 'Debit', 'Bahan kemasan yang belum dipakai. Jumlah karung stok tidak otomatis menjadi nilai akun ini.'],
                ['11304', 'Persediaan Dalam Proses (WIP)', 'Harta', 'Persediaan', 'Debit', 'Nilai bahan yang sedang blower/jemur atau menunggu press, ditambah biaya pengolahan yang layak dialokasikan. Pemindahan dari bahan ke WIP tidak menambah total persediaan.'],
                ['11305', 'Persediaan Barang Jadi Rumput Laut', 'Harta', 'Persediaan', 'Debit', 'Nilai biaya rumput laut yang selesai press, timbang, dan packing. Dipindahkan dari WIP; bukan harga jual dan bukan pencatatan stok kedua. Area/kualitas dirinci pada catatan gudang.'],
                ['11401', 'Uang Muka Pembelian', 'Harta', 'Uang Muka dan Biaya Dibayar di Muka', 'Debit', 'Pembayaran pembelian sebelum barang diterima.'],
                ['11402', 'Biaya Dibayar di Muka', 'Harta', 'Uang Muka dan Biaya Dibayar di Muka', 'Debit', 'Sewa/asuransi dibayar di muka untuk periode berikutnya.'],
                ['12101', 'Tanah', 'Harta', 'Aset Tetap', 'Debit', 'Tanah milik usaha.'],
                ['12102', 'Bangunan Gudang', 'Harta', 'Aset Tetap', 'Debit', 'Bangunan gudang milik usaha.'],
                ['12103', 'Mesin dan Peralatan', 'Harta', 'Aset Tetap', 'Debit', 'Timbangan, alat pengering, dan mesin operasional.'],
                ['12104', 'Kendaraan', 'Harta', 'Aset Tetap', 'Debit', 'Kendaraan angkut milik usaha.'],
                ['12201', 'Akumulasi Penyusutan Bangunan', 'Harta', 'Akumulasi Penyusutan', 'Kredit', 'Akun pengurang nilai bangunan gudang.'],
                ['12202', 'Akumulasi Penyusutan Mesin dan Peralatan', 'Harta', 'Akumulasi Penyusutan', 'Kredit', 'Akun pengurang nilai mesin dan peralatan.'],
                ['12203', 'Akumulasi Penyusutan Kendaraan', 'Harta', 'Akumulasi Penyusutan', 'Kredit', 'Akun pengurang nilai kendaraan.'],
                ['21101', 'Utang Pembelian kepada Petani', 'Utang', 'Utang Lancar', 'Kredit', 'Kewajiban pembelian panen yang belum dibayar; bukan pinjaman yang diberikan kepada petani.'],
                ['21102', 'Utang Usaha Pemasok', 'Utang', 'Utang Lancar', 'Kredit', 'Tagihan pemasok bahan, kemasan, atau jasa yang belum dibayar.'],
                ['21103', 'Utang Biaya Operasional', 'Utang', 'Utang Lancar', 'Kredit', 'Gaji, listrik, angkutan, dan biaya terutang lainnya.'],
                ['21104', 'Utang Pajak', 'Utang', 'Utang Lancar', 'Kredit', 'Kewajiban pajak yang telah dihitung dan belum disetor.'],
                ['21201', 'Uang Muka Konsumen', 'Utang', 'Uang Muka Penjualan', 'Kredit', 'Penerimaan dari konsumen sebelum kewajiban penyerahan barang dipenuhi.'],
                ['21301', 'Pinjaman Jangka Pendek', 'Utang', 'Pinjaman Usaha', 'Kredit', 'Pinjaman yang diterima perusahaan dengan jatuh tempo jangka pendek.'],
                ['22101', 'Pinjaman Jangka Panjang', 'Utang', 'Pinjaman Usaha', 'Kredit', 'Pinjaman yang diterima perusahaan dengan jatuh tempo jangka panjang.'],
                ['31101', 'Modal Pemilik', 'Modal', 'Modal dan Penarikan Pemilik', 'Kredit', 'Setoran modal pemilik; bukan pendapatan penjualan.'],
                ['31102', 'Prive Pemilik', 'Modal', 'Modal dan Penarikan Pemilik', 'Debit', 'Pengambilan dana oleh pemilik; pengurang modal, bukan biaya operasional.'],
                ['32101', 'Laba Ditahan', 'Modal', 'Laba Usaha', 'Kredit', 'Akumulasi laba periode terdahulu yang belum dibagikan.'],
                ['32102', 'Laba / Rugi Tahun Berjalan', 'Modal', 'Laba Usaha', 'Kredit', 'Hasil usaha periode berjalan setelah proses penutupan; rugi menghasilkan saldo debit.'],
                ['41101', 'Penjualan Rumput Laut Basah', 'Pendapatan', 'Pendapatan Usaha', 'Kredit', 'Penjualan rumput laut basah kepada pabrik/konsumen.'],
                ['41102', 'Penjualan Rumput Laut Kering', 'Pendapatan', 'Pendapatan Usaha', 'Kredit', 'Penjualan rumput laut kering kepada pabrik/konsumen.'],
                ['41201', 'Retur dan Potongan Penjualan', 'Pendapatan', 'Pengurang Pendapatan', 'Debit', 'Retur atau potongan komersial penjualan; potongan berat perlu dinilai sesuai substansinya.'],
                ['42101', 'Pendapatan Jasa', 'Pendapatan', 'Pendapatan Lainnya', 'Kredit', 'Jasa pengeringan/angkutan jika ditagihkan terpisah kepada pelanggan.'],
                ['42102', 'Pendapatan Lain-lain', 'Pendapatan', 'Pendapatan Lainnya', 'Kredit', 'Pendapatan nonpenjualan; tidak termasuk setoran modal, pinjaman diterima, atau pelunasan piutang.'],
                ['51101', 'Harga Pokok Penjualan Rumput Laut Basah', 'Biaya', 'Harga Pokok Penjualan', 'Debit', 'Nilai persediaan basah yang terjual; pembelian stok belum terjual tetap persediaan.'],
                ['51102', 'Harga Pokok Penjualan Rumput Laut Kering', 'Biaya', 'Harga Pokok Penjualan', 'Debit', 'Nilai persediaan kering yang terjual, termasuk biaya perolehan yang dialokasikan.'],
                ['51103', 'Harga Pokok Penjualan Barang Jadi Rumput Laut', 'Biaya', 'Harga Pokok Penjualan', 'Debit', 'Biaya barang jadi yang terjual: debit akun ini dan kredit Persediaan Barang Jadi. Jangan mencatat lagi ke HPP basah/kering untuk barang yang sama.'],
                ['51201', 'Kerugian Persediaan', 'Biaya', 'Kerugian Persediaan', 'Debit', 'Kehilangan/kerusakan atau susut abnormal; susut normal pengeringan dialokasikan ke biaya persediaan.'],
                ['52101', 'Biaya Transportasi dan Pengiriman', 'Biaya', 'Biaya Operasional', 'Debit', 'Angkutan penjualan/operasional; angkutan pembelian yang langsung terkait masuk biaya perolehan stok.'],
                ['52102', 'Biaya Bahan Bakar', 'Biaya', 'Biaya Operasional', 'Debit', 'BBM kendaraan operasional.'],
                ['52103', 'Biaya Gaji dan Upah', 'Biaya', 'Biaya Operasional', 'Debit', 'Gaji/upah operasional; upah pengolahan langsung dapat dialokasikan ke persediaan.'],
                ['52104', 'Biaya Listrik dan Air', 'Biaya', 'Biaya Operasional', 'Debit', 'Utilitas operasional kantor/gudang.'],
                ['52105', 'Biaya Sewa', 'Biaya', 'Biaya Operasional', 'Debit', 'Sewa gudang/kantor yang menjadi beban periode berjalan.'],
                ['52106', 'Biaya Perawatan dan Perbaikan', 'Biaya', 'Biaya Operasional', 'Debit', 'Pemeliharaan rutin mesin, gudang, dan kendaraan.'],
                ['52107', 'Biaya Karung dan Kemasan', 'Biaya', 'Biaya Operasional', 'Debit', 'Kemasan yang dipakai untuk penjualan/operasional.'],
                ['52108', 'Biaya Konsumsi', 'Biaya', 'Biaya Operasional', 'Debit', 'Konsumsi karyawan dan kegiatan operasional.'],
                ['52109', 'Biaya Administrasi dan ATK', 'Biaya', 'Biaya Operasional', 'Debit', 'Administrasi, alat tulis, dan kebutuhan kantor.'],
                ['52110', 'Biaya Perjalanan Dinas', 'Biaya', 'Biaya Operasional', 'Debit', 'Perjalanan untuk kegiatan usaha.'],
                ['52111', 'Biaya Telepon dan Internet', 'Biaya', 'Biaya Operasional', 'Debit', 'Telepon dan internet untuk administrasi dan komunikasi usaha.'],
                ['52112', 'Biaya Asuransi', 'Biaya', 'Biaya Operasional', 'Debit', 'Bagian premi yang menjadi beban periode berjalan. Bagian untuk periode mendatang dicatat sebagai Biaya Dibayar di Muka.'],
                ['52201', 'Biaya Penyusutan', 'Biaya', 'Penyusutan', 'Debit', 'Penyusutan aset tetap untuk periode berjalan.'],
                ['53101', 'Biaya Administrasi Bank', 'Biaya', 'Biaya Keuangan', 'Debit', 'Biaya transfer dan administrasi rekening usaha.'],
                ['53102', 'Biaya Bunga Pinjaman', 'Biaya', 'Biaya Keuangan', 'Debit', 'Bunga pinjaman yang diterima perusahaan; pembayaran pokok mengurangi utang.'],
                ['54101', 'Biaya Pajak', 'Biaya', 'Biaya Lainnya', 'Debit', 'Pajak yang menjadi beban usaha sesuai jenis dan perlakuannya.'],
                ['54102', 'Biaya Lain-lain', 'Biaya', 'Biaya Lainnya', 'Debit', 'Biaya usaha yang tidak termasuk kelompok di atas.'],
            ];
            foreach ($accounts as [$code, $name, $category, $group, $balance, $description]) {
                ChartOfAccount::firstOrCreate(['code' => $code], [
                    'name' => $name, 'category' => $category, 'account_group' => $group,
                    'normal_balance' => $balance, 'description' => $description, 'is_active' => true,
                ]);
            }
            foreach (['read', 'create', 'update'] as $action) {
                $permission = Permission::firstOrCreate(['name' => 'coa.'.$action, 'guard_name' => 'web']);
                foreach (Role::whereIn('name', ['superadmin', 'owner'])->get() as $role) {
                    $role->givePermissionTo($permission);
                }
            }
            $finance = Menu::whereNull('parent_id')->where('name', 'Keuangan')->firstOrFail();
            Menu::updateOrCreate(['route' => 'coa.index'], [
                'name' => 'COA', 'icon' => 'list-tree', 'url' => 'keuangan/coa',
                'parent_id' => $finance->id, 'order' => 7, 'is_active' => true, 'permission' => 'coa.read',
            ]);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
