<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestingController extends Controller
{
    public function dashboard()
    {
        return view('pages.dashboard.index');
    }

    public function dataTable()
    {

        $columns = [
            ['key' => 'name', 'label' => 'Nama'],
            ['key' => 'phone', 'label' => 'No. Telp'],
            ['key' => 'position', 'label' => 'Posisi'],
            ['key' => 'location', 'label' => 'Lokasi'],
            ['key' => 'status', 'label' => 'Status', 'render' => 'status', 'sortable' => true],
            ['key' => 'action', 'label' => 'Action', 'render' => 'action'],
        ];

        $data = $technicians = [
            [
                'id' => 1,
                'name' => 'Ahmad Fauzi',
                'phone' => '081234567801',
                'position' => 'Senior Technician',
                'location' => 'Balikpapan',
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'name' => 'Budi Santoso',
                'phone' => '081234567802',
                'position' => 'Junior Technician',
                'location' => 'Samarinda',
                'status' => 'Inactive',
            ],
            [
                'id' => 3,
                'name' => 'Rizky Pratama',
                'phone' => '081234567803',
                'position' => 'Field Technician',
                'location' => 'Jakarta Selatan',
                'status' => 'Active',
            ],
            [
                'id' => 4,
                'name' => 'Dedi Kurniawan',
                'phone' => '081234567804',
                'position' => 'Maintenance Technician',
                'location' => 'Bandung',
                'status' => 'Active',
            ],
            [
                'id' => 5,
                'name' => 'Andi Wijaya',
                'phone' => '081234567805',
                'position' => 'Network Technician',
                'location' => 'Surabaya',
                'status' => 'Inactive',
            ],
            [
                'id' => 6,
                'name' => 'Fajar Maulana',
                'phone' => '081234567806',
                'position' => 'Electrical Technician',
                'location' => 'Yogyakarta',
                'status' => 'Active',
            ],
            [
                'id' => 7,
                'name' => 'Hendra Saputra',
                'phone' => '081234567807',
                'position' => 'Field Technician',
                'location' => 'Bekasi',
                'status' => 'Active',
            ],
            [
                'id' => 8,
                'name' => 'Ilham Ramadhan',
                'phone' => '081234567808',
                'position' => 'Support Technician',
                'location' => 'Depok',
                'status' => 'Inactive',
            ],
            [
                'id' => 9,
                'name' => 'Rian Setiawan',
                'phone' => '081234567809',
                'position' => 'Senior Technician',
                'location' => 'Bogor',
                'status' => 'Active',
            ],
            [
                'id' => 10,
                'name' => 'Yoga Prakoso',
                'phone' => '081234567810',
                'position' => 'Installation Technician',
                'location' => 'Tangerang',
                'status' => 'Active',
            ],
        ];

        return view('pages.data-table.index', compact('data', 'columns'));
    }
}
