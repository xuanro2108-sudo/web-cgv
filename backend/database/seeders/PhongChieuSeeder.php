<?php

namespace Database\Seeders;

use App\Models\PhongChieu;
use Illuminate\Database\Seeder;

class PhongChieuSeeder extends Seeder
{
    public function run(): void
    {
        PhongChieu::create([
            'maPhong' => 'P001',
            'tenPhong' => 'PhĂ²ng 1',
            'sucChua' => 80,
            'trangThai' => 'HOAT_DONG',
        ]);

        PhongChieu::create([
            'maPhong' => 'P002',
            'tenPhong' => 'PhĂ²ng 2',
            'sucChua' => 100,
            'trangThai' => 'HOAT_DONG',
        ]);

        PhongChieu::create([
            'maPhong' => 'P003',
            'tenPhong' => 'PhĂ²ng 3',
            'sucChua' => 120,
            'trangThai' => 'HOAT_DONG',
        ]);

        PhongChieu::create([
            'maPhong' => 'P004',
            'tenPhong' => 'PhĂ²ng 4',
            'sucChua' => 60,
            'trangThai' => 'HOAT_DONG',
        ]);
    }
}
