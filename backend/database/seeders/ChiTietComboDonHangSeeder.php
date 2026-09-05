<?php

namespace Database\Seeders;

use App\Models\ChiTietComboDonHang;
use Illuminate\Database\Seeder;

class ChiTietComboDonHangSeeder extends Seeder
{
    public function run(): void
    {
        // DH001 mua 1 Combo Solo
        ChiTietComboDonHang::create([
            'maChiTiet' => 'CTDH001',
            'maDonHang' => 'DH001',
            'maCombo' => 'CB001',
            'soLuong' => 1,
            'donGia' => 70000,
            'thanhTien' => 70000,
        ]);

        // DH002 mua 1 Combo Couple
        ChiTietComboDonHang::create([
            'maChiTiet' => 'CTDH002',
            'maDonHang' => 'DH002',
            'maCombo' => 'CB002',
            'soLuong' => 1,
            'donGia' => 120000,
            'thanhTien' => 120000,
        ]);

        // DH003 mua 1 Combo Family
        ChiTietComboDonHang::create([
            'maChiTiet' => 'CTDH003',
            'maDonHang' => 'DH003',
            'maCombo' => 'CB003',
            'soLuong' => 1,
            'donGia' => 180000,
            'thanhTien' => 180000,
        ]);

        // DH004 mua 2 Combo Solo
        ChiTietComboDonHang::create([
            'maChiTiet' => 'CTDH004',
            'maDonHang' => 'DH004',
            'maCombo' => 'CB001',
            'soLuong' => 2,
            'donGia' => 70000,
            'thanhTien' => 140000,
        ]);
    }
}
