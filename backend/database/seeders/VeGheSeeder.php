<?php

namespace Database\Seeders;

use App\Models\VeGhe;
use Illuminate\Database\Seeder;

class VeGheSeeder extends Seeder
{
    public function run(): void
    {
        // DH001 - LC001 - PhĂ²ng 1
        // A1: THUONG = 70.000
        VeGhe::create([
            'maVe' => 'VE001',
            'maDonHang' => 'DH001',
            'maLichChieu' => 'LC001',
            'maGhe' => 'G001',
            'giaVe' => 70000,
            'trangThai' => 'DA_DAT',
            'ngayTao' => '2026-09-05 09:05:00',
        ]);

        // C1: VIP = 70.000 Ă— 1.2 = 84.000
        VeGhe::create([
            'maVe' => 'VE002',
            'maDonHang' => 'DH001',
            'maLichChieu' => 'LC001',
            'maGhe' => 'G021',
            'giaVe' => 84000,
            'trangThai' => 'DA_DAT',
            'ngayTao' => '2026-09-05 09:05:00',
        ]);

        // DH002 - LC002 - PhĂ²ng 2
        // A1: THUONG = 80.000
        VeGhe::create([
            'maVe' => 'VE003',
            'maDonHang' => 'DH002',
            'maLichChieu' => 'LC002',
            'maGhe' => 'G081',
            'giaVe' => 80000,
            'trangThai' => 'DA_DAT',
            'ngayTao' => '2026-09-05 10:05:00',
        ]);

        // C1: VIP = 80.000 Ă— 1.2 = 96.000
        VeGhe::create([
            'maVe' => 'VE004',
            'maDonHang' => 'DH002',
            'maLichChieu' => 'LC002',
            'maGhe' => 'G101',
            'giaVe' => 96000,
            'trangThai' => 'DA_DAT',
            'ngayTao' => '2026-09-05 10:05:00',
        ]);

        // DH003 - LC003 - PhĂ²ng 3
        // A1: THUONG = 75.000
        VeGhe::create([
            'maVe' => 'VE005',
            'maDonHang' => 'DH003',
            'maLichChieu' => 'LC003',
            'maGhe' => 'G181',
            'giaVe' => 75000,
            'trangThai' => 'DA_DAT',
            'ngayTao' => '2026-09-05 11:05:00',
        ]);

        // C1: VIP = 75.000 Ă— 1.2 = 90.000
        VeGhe::create([
            'maVe' => 'VE006',
            'maDonHang' => 'DH003',
            'maLichChieu' => 'LC003',
            'maGhe' => 'G201',
            'giaVe' => 90000,
            'trangThai' => 'DA_DAT',
            'ngayTao' => '2026-09-05 11:05:00',
        ]);

        // DH004 - LC004 - PhĂ²ng 4
        // A1: THUONG = 90.000
        VeGhe::create([
            'maVe' => 'VE007',
            'maDonHang' => 'DH004',
            'maLichChieu' => 'LC004',
            'maGhe' => 'G301',
            'giaVe' => 90000,
            'trangThai' => 'DA_DAT',
            'ngayTao' => '2026-09-05 14:05:00',
        ]);

        // C1: VIP = 90.000 Ă— 1.2 = 108.000
        VeGhe::create([
            'maVe' => 'VE008',
            'maDonHang' => 'DH004',
            'maLichChieu' => 'LC004',
            'maGhe' => 'G321',
            'giaVe' => 108000,
            'trangThai' => 'DA_DAT',
            'ngayTao' => '2026-09-05 14:05:00',
        ]);

        // F1-F2: GHáº¾ ÄĂ”I
        // Má»—i gháº¿ váº­t lĂ½ = 108.000
        // Cáº£ cáº·p = 216.000
        VeGhe::create([
            'maVe' => 'VE009',
            'maDonHang' => 'DH004',
            'maLichChieu' => 'LC004',
            'maGhe' => 'G351',
            'giaVe' => 108000,
            'trangThai' => 'DA_DAT',
            'ngayTao' => '2026-09-05 14:05:00',
        ]);

        VeGhe::create([
            'maVe' => 'VE010',
            'maDonHang' => 'DH004',
            'maLichChieu' => 'LC004',
            'maGhe' => 'G352',
            'giaVe' => 108000,
            'trangThai' => 'DA_DAT',
            'ngayTao' => '2026-09-05 14:05:00',
        ]);
    }
}
