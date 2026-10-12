<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class InvoiceMenuSeeder extends Seeder
{
    public function run(): void
    {
        $note = Menu::where('route', 'surat-jalan.index')->firstOrFail();
        Menu::updateOrCreate(['route' => 'invoice.index'], [
            'name' => 'Invoice', 'url' => 'invoice', 'parent_id' => $note->parent_id,
            'order' => $note->order + 1, 'is_active' => true, 'permission' => 'invoice.read',
        ]);
    }
}
