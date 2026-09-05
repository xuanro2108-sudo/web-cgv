<?php

namespace Database\Seeders;

use App\Models\ChiTietCombo;
use Illuminate\Database\Seeder;

class ChiTietComboSeeder extends Seeder
{
    public function run(): void
    {
        // Combo Solo
        ChiTietCombo::create([
            'maCTCombo' => 'CT001',
            'maCombo' => 'CB001',
            'maSP' => 'SP001',
            'soLuong' => 1,
        ]);

        ChiTietCombo::create([
            'maCTCombo' => 'CT002',
            'maCombo' => 'CB001',
            'maSP' => 'SP003',
            'soLuong' => 1,
        ]);

        // Combo Couple
        ChiTietCombo::create([
            'maCTCombo' => 'CT003',
            'maCombo' => 'CB002',
            'maSP' => 'SP001',
            'soLuong' => 1,
        ]);

        ChiTietCombo::create([
            'maCTCombo' => 'CT004',
            'maCombo' => 'CB002',
            'maSP' => 'SP003',
            'soLuong' => 2,
        ]);

        // Combo Family
        ChiTietCombo::create([
            'maCTCombo' => 'CT005',
            'maCombo' => 'CB003',
            'maSP' => 'SP001',
            'soLuong' => 2,
        ]);

        ChiTietCombo::create([
            'maCTCombo' => 'CT006',
            'maCombo' => 'CB003',
            'maSP' => 'SP003',
            'soLuong' => 4,
        ]);

        // Combo Premium
        ChiTietCombo::create([
            'maCTCombo' => 'CT007',
            'maCombo' => 'CB004',
            'maSP' => 'SP002',
            'soLuong' => 1,
        ]);

        ChiTietCombo::create([
            'maCTCombo' => 'CT008',
            'maCombo' => 'CB004',
            'maSP' => 'SP003',
            'soLuong' => 1,
        ]);

        ChiTietCombo::create([
            'maCTCombo' => 'CT009',
            'maCombo' => 'CB004',
            'maSP' => 'SP006',
            'soLuong' => 1,
        ]);
    }
}
