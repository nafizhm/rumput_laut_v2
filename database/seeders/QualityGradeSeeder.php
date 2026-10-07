<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\QualityGrade;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class QualityGradeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $grades = [
                ['Q00', 'Belum Dinilai', 'Barang menunggu pemeriksaan kualitas.'],
                ['Q01', 'Grade A', 'Memenuhi spesifikasi kualitas tertinggi yang disepakati dengan client.'],
                ['Q02', 'Grade B', 'Memenuhi spesifikasi kualitas standar yang disepakati dengan client.'],
                ['Q03', 'Grade C', 'Kualitas di bawah standar utama; penggunaan atau penjualan terbatas sesuai keputusan pemeriksa.'],
                ['Q04', 'Perlu Proses Ulang', 'Memerlukan pengeringan, pembersihan, atau pengolahan ulang sebelum dinilai kembali.'],
                ['Q05', 'Reject', 'Tidak layak menjadi stok siap jual. Penandaan ini tidak otomatis menghapus stok fisik.'],
            ];
            foreach ($grades as $order => [$code, $name, $description]) {
                QualityGrade::firstOrCreate(['code' => $code], [
                    'name' => $name, 'description' => $description, 'criteria' => null,
                    'sort_order' => $order, 'is_active' => true,
                ]);
            }
            foreach (['read', 'create', 'update'] as $action) {
                $permission = Permission::firstOrCreate(['name' => 'quality.'.$action, 'guard_name' => 'web']);
                foreach (Role::whereIn('name', ['superadmin', 'owner', 'admin'])->get() as $role) {
                    $role->givePermissionTo($permission);
                }
            }
            $parent = Menu::whereNull('parent_id')->where('name', 'Master')->firstOrFail();
            $menu = Menu::firstOrNew(['route' => 'kualitas-barang.index']);
            $order = $menu->exists ? $menu->order : ((int) $parent->children()->max('order') + 1);
            $menu->fill([
                'name' => 'Kualitas Barang', 'icon' => 'badge-check', 'url' => 'kualitas-barang',
                'parent_id' => $parent->id, 'order' => $order, 'permission' => 'quality.read', 'is_active' => true,
            ])->save();
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
