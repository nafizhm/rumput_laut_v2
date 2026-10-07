<?php
namespace Database\Seeders;

use App\Models\{Inventory, WarehouseArea, QualityGrade, Menu};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\{Permission, Role};
use Spatie\Permission\PermissionRegistrar;

class GoodsReceivingSeeder extends Seeder
{
    public function run(): void {
        DB::transaction(function () {
            $inventory = Inventory::firstOrCreate(['stock_stage' => 'material'], ['item_name' => 'Bahan Masuk', 'current_stock_kg' => 0, 'current_sack_count' => 0]);
            foreach ([['BM-A', 'Area Bahan Grade A', ['Q01']], ['BM-B', 'Area Bahan Grade B', ['Q02']], ['BM-C', 'Area Bahan Grade C', ['Q03']], ['BM-K', 'Area Pemeriksaan / Karantina', ['Q00', 'Q04', 'Q05']]] as [$code, $name, $codes]) {
                $area = WarehouseArea::firstOrCreate(['code' => $code], [
                    'name' => $name, 'inventory_id' => $inventory->id, 'usage' => 'receiving',
                    'description' => 'Area awal; sesuaikan dengan layout gudang client.', 'is_active' => true,
                ]);
                if ($area->wasRecentlyCreated) $area->grades()->sync(QualityGrade::whereIn('code', $codes)->pluck('id')->all());
            }
            foreach (['area.read', 'area.create', 'area.update', 'receiving.read', 'receiving.create', 'receiving.update', 'receiving.confirm', 'receiving.delete'] as $name) {
                $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                foreach (Role::whereIn('name', ['superadmin', 'owner', 'admin'])->get() as $role) $role->givePermissionTo($permission);
            }
            $master = Menu::whereNull('parent_id')->where('name', 'Master')->firstOrFail();
            $warehouse = Menu::whereNull('parent_id')->where('name', 'Manajemen Gudang')->firstOrFail();
            foreach ([['area-gudang.index', 'Area Gudang', 'area-gudang', $master->id, 'area.read'], ['penerimaan-barang.index', 'Penerimaan Barang', 'penerimaan-barang', $warehouse->id, 'receiving.read']] as [$route, $name, $url, $parentId, $permission]) {
                $menu = Menu::firstOrNew(['route' => $route]);
                $menu->fill(['name' => $name, 'url' => $url, 'parent_id' => $parentId, 'order' => $menu->exists ? $menu->order : ((int) Menu::where('parent_id', $parentId)->max('order') + 1), 'is_active' => true, 'permission' => $permission])->save();
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
