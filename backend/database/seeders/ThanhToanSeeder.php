<?php

namespace Database\Seeders;

use App\Models\ThanhToan;
use Illuminate\Database\Seeder;

class ThanhToanSeeder extends Seeder
{
    public function run(): void
    {
        ThanhToan::create([
            'maTT' => 'TT001',
            'maDonHang' => 'DH001',
            'maGiaoDich' => 'GD001',
            'soTien' => 179200,
            'phuongThuc' => 'MOMO',
            'ngayThanhToan' => '2026-09-05 09:05:00',
            'trangThai' => 'CHO_THANH_TOAN',
        ]);

        ThanhToan::create([
            'maTT' => 'TT002',
            'maDonHang' => 'DH002',
            'maGiaoDich' => 'GD002',
            'soTien' => 296000,
            'phuongThuc' => 'VNPAY',
            'ngayThanhToan' => '2026-09-05 10:05:00',
            'trangThai' => 'THANH_CONG',
        ]);

        ThanhToan::create([
            'maTT' => 'TT003',
            'maDonHang' => 'DH003',
            'maGiaoDich' => 'GD003',
            'soTien' => 345000,
            'phuongThuc' => 'TIEN_MAT',
            'ngayThanhToan' => '2026-09-05 11:10:00',
            'trangThai' => 'THANH_CONG',
        ]);

        ThanhToan::create([
            'maTT' => 'TT004',
            'maDonHang' => 'DH004',
            'maGiaoDich' => 'GD004',
            'soTien' => 470900,
            'phuongThuc' => 'THE',
            'ngayThanhToan' => '2026-09-05 14:10:00',
            'trangThai' => 'THANH_CONG',
        ]);
    }
}
