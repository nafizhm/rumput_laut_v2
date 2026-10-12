<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class DeliveryNoteMenuSeeder extends Seeder
{
    public function run(): void
    {
        $po = Menu::where('route', 'po-konsumen.index')->firstOrFail();
        Menu::updateOrCreate(['route' => 'surat-jalan.index'], [
            'name' => 'Surat jalan', 'url' => 'surat-jalan', 'parent_id' => $po->parent_id,
            'order' => $po->order + 1, 'is_active' => true, 'permission' => 'surat-jalan.read',
        ]);
    }
}
