<?php
namespace Database\Seeders;

use App\Models\Combo;
use Illuminate\Database\Seeder;

class ComboSeeder extends Seeder
{
    public function run(): void
    {
        Combo::create([
            'maCombo' => 'CB001',
            'tenCombo' => 'Combo Solo',
            'donGia' => 70000,
            'moTa' => '1 bắp rang bơ và 1 Coca Cola.',
            'hinhAnh' => 'combo-solo.jpg',
            'trangThai' => 'HOAT_DONG',
        ]);

        Combo::create([
            'maCombo' => 'CB002',
            'tenCombo' => 'Combo Couple',
            'donGia' => 120000,
            'moTa' => '1 bắp rang bơ lớn và 2 Coca Cola.',
            'hinhAnh' => 'combo-couple.jpg',
            'trangThai' => 'HOAT_DONG',
        ]);

        Combo::create([
            'maCombo' => 'CB003',
            'tenCombo' => 'Combo Family',
            'donGia' => 180000,
            'moTa' => '2 bắp rang và 4 nước ngọt.',
            'hinhAnh' => 'combo-family.jpg',
            'trangThai' => 'HOAT_DONG',
        ]);

        Combo::create([
            'maCombo' => 'CB004',
            'tenCombo' => 'Combo Premium',
            'donGia' => 150000,
            'moTa' => 'Bắp rang phô mai, Coca Cola và snack.',
            'hinhAnh' => 'combo-premium.jpg',
            'trangThai' => 'HOAT_DONG',
        ]);
    }
}
