<?php

namespace Database\Seeders;

use App\Models\PhongChieu;
use Illuminate\Database\Seeder;

class PhongChieuSeeder extends Seeder
{
    public function run(): void
    {
        PhongChieu::updateOrCreate(
            ['maPhong' => 'P001'],
            [
                'tenPhong' => 'Phòng 1',
                'sucChua' => 80,
                'trangThai' => 'HOAT_DONG',
            ]
        );

        PhongChieu::updateOrCreate(
            ['maPhong' => 'P002'],
            [
                'tenPhong' => 'Phòng 2',
                'sucChua' => 100,
                'trangThai' => 'HOAT_DONG',
            ]
        );

        PhongChieu::updateOrCreate(
            ['maPhong' => 'P003'],
            [
                'tenPhong' => 'Phòng 3',
                'sucChua' => 120,
                'trangThai' => 'HOAT_DONG',
            ]
        );

        PhongChieu::updateOrCreate(
            ['maPhong' => 'P004'],
            [
                'tenPhong' => 'Phòng 4',
                'sucChua' => 60,
                'trangThai' => 'HOAT_DONG',
            ]
        );
    }
}