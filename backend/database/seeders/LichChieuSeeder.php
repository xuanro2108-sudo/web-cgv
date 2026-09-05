<?php

namespace Database\Seeders;

use App\Models\LichChieu;
use Illuminate\Database\Seeder;

class LichChieuSeeder extends Seeder
{
    public function run(): void
    {
        // Avengers: Endgame - PhĂ²ng 1
        LichChieu::create([
            'maLichChieu' => 'LC001',
            'maPhim' => 'PHIM001',
            'maPhong' => 'P001',
            'ngayChieu' => '2026-09-06',
            'gioBatDau' => '09:00',
            'gioKetThuc' => '12:01',
            'giaVeCoBan' => 70000,
            'trangThai' => 'HOAT_DONG',
        ]);

        // Avengers: Endgame - PhĂ²ng 2
        LichChieu::create([
            'maLichChieu' => 'LC002',
            'maPhim' => 'PHIM001',
            'maPhong' => 'P002',
            'ngayChieu' => '2026-09-06',
            'gioBatDau' => '14:00',
            'gioKetThuc' => '17:01',
            'giaVeCoBan' => 80000,
            'trangThai' => 'HOAT_DONG',
        ]);

        // Dune: Part Two - PhĂ²ng 3
        LichChieu::create([
            'maLichChieu' => 'LC003',
            'maPhim' => 'PHIM002',
            'maPhong' => 'P003',
            'ngayChieu' => '2026-09-06',
            'gioBatDau' => '10:00',
            'gioKetThuc' => '12:46',
            'giaVeCoBan' => 75000,
            'trangThai' => 'HOAT_DONG',
        ]);

        // Dune: Part Two - PhĂ²ng 4
        LichChieu::create([
            'maLichChieu' => 'LC004',
            'maPhim' => 'PHIM002',
            'maPhong' => 'P004',
            'ngayChieu' => '2026-09-06',
            'gioBatDau' => '19:00',
            'gioKetThuc' => '21:46',
            'giaVeCoBan' => 90000,
            'trangThai' => 'HOAT_DONG',
        ]);

        // Inside Out 2 - PhĂ²ng 1
        LichChieu::create([
            'maLichChieu' => 'LC005',
            'maPhim' => 'PHIM003',
            'maPhong' => 'P001',
            'ngayChieu' => '2026-09-07',
            'gioBatDau' => '09:30',
            'gioKetThuc' => '11:06',
            'giaVeCoBan' => 65000,
            'trangThai' => 'HOAT_DONG',
        ]);

        // Inside Out 2 - PhĂ²ng 2
        LichChieu::create([
            'maLichChieu' => 'LC006',
            'maPhim' => 'PHIM003',
            'maPhong' => 'P002',
            'ngayChieu' => '2026-09-07',
            'gioBatDau' => '16:00',
            'gioKetThuc' => '17:36',
            'giaVeCoBan' => 80000,
            'trangThai' => 'HOAT_DONG',
        ]);

        // Avatar 3 - PhĂ²ng 3
        LichChieu::create([
            'maLichChieu' => 'LC007',
            'maPhim' => 'PHIM004',
            'maPhong' => 'P003',
            'ngayChieu' => '2026-12-18',
            'gioBatDau' => '13:00',
            'gioKetThuc' => '16:00',
            'giaVeCoBan' => 100000,
            'trangThai' => 'HOAT_DONG',
        ]);

        // Avatar 3 - PhĂ²ng 4
        LichChieu::create([
            'maLichChieu' => 'LC008',
            'maPhim' => 'PHIM004',
            'maPhong' => 'P004',
            'ngayChieu' => '2026-12-18',
            'gioBatDau' => '19:30',
            'gioKetThuc' => '22:30',
            'giaVeCoBan' => 110000,
            'trangThai' => 'HOAT_DONG',
        ]);
    }
}
