<?php

namespace Database\Seeders;

use App\Models\DonHang;
use Illuminate\Database\Seeder;

class DonHangSeeder extends Seeder
{
    public function run(): void
    {
        // ÄÆ¡n hĂ ng online cá»§a khĂ¡ch KH001
        DonHang::create([
            'maDonHang' => 'DH001',
            'maKH' => 'KH001',
            'maNV' => null,
            'maKM' => 'KM001',
            'kieuDat' => 'ONLINE',
            'ngayDat' => '2026-09-05 09:00:00',
            'tongTien' => 0,
            'maQR' => 'QR-DH001',
            'trangThai' => 'CHO_THANH_TOAN',
        ]);

        // ÄÆ¡n hĂ ng online cá»§a khĂ¡ch KH002
        DonHang::create([
            'maDonHang' => 'DH002',
            'maKH' => 'KH002',
            'maNV' => null,
            'maKM' => 'KM002',
            'kieuDat' => 'ONLINE',
            'ngayDat' => '2026-09-05 10:00:00',
            'tongTien' => 0,
            'maQR' => 'QR-DH002',
            'trangThai' => 'DA_THANH_TOAN',
        ]);

        // ÄÆ¡n hĂ ng táº¡i quáº§y do nhĂ¢n viĂªn NV002 xá»­ lĂ½
        DonHang::create([
            'maDonHang' => 'DH003',
            'maKH' => 'KH003',
            'maNV' => 'NV002',
            'maKM' => null,
            'kieuDat' => 'TAI_QUAY',
            'ngayDat' => '2026-09-05 11:00:00',
            'tongTien' => 0,
            'maQR' => 'QR-DH003',
            'trangThai' => 'DA_THANH_TOAN',
        ]);

        // ÄÆ¡n hĂ ng táº¡i quáº§y do nhĂ¢n viĂªn NV003 xá»­ lĂ½
        DonHang::create([
            'maDonHang' => 'DH004',
            'maKH' => 'KH004',
            'maNV' => 'NV003',
            'maKM' => 'KM003',
            'kieuDat' => 'TAI_QUAY',
            'ngayDat' => '2026-09-05 14:00:00',
            'tongTien' => 0,
            'maQR' => 'QR-DH004',
            'trangThai' => 'DA_THANH_TOAN',
        ]);
    }
}
