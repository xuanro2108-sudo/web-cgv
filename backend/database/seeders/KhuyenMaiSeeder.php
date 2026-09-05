<?php

namespace Database\Seeders;

use App\Models\KhuyenMai;
use Illuminate\Database\Seeder;

class KhuyenMaiSeeder extends Seeder
{
    public function run(): void
    {
        KhuyenMai::create([
            'maKM' => 'KM001',
            'tenKM' => 'Giáº£m 20% cho khĂ¡ch hĂ ng má»›i',
            'hinhThuc' => 'GIAM_PHAN_TRAM',
            'giaTri' => 20,
            'donToiThieu' => 100000,
            'ngayBatDau' => '2026-09-01',
            'ngayKetThuc' => '2026-09-30',
            'trangThai' => 'HOAT_DONG',
        ]);

        KhuyenMai::create([
            'maKM' => 'KM002',
            'tenKM' => 'Giáº£m 50000Ä‘ cho Ä‘Æ¡n tá»« 300000Ä‘',
            'hinhThuc' => 'GIAM_GIA',
            'giaTri' => 50000,
            'donToiThieu' => 300000,
            'ngayBatDau' => '2026-09-01',
            'ngayKetThuc' => '2026-09-30',
            'trangThai' => 'HOAT_DONG',
        ]);

        KhuyenMai::create([
            'maKM' => 'KM003',
            'tenKM' => 'Æ¯u Ä‘Ă£i cuá»‘i tuáº§n',
            'hinhThuc' => 'GIAM_PHAN_TRAM',
            'giaTri' => 15,
            'donToiThieu' => 150000,
            'ngayBatDau' => '2026-09-01',
            'ngayKetThuc' => '2026-12-31',
            'trangThai' => 'HOAT_DONG',
        ]);

        KhuyenMai::create([
            'maKM' => 'KM004',
            'tenKM' => 'Khuyáº¿n mĂ£i Ä‘áº·c biá»‡t',
            'hinhThuc' => 'GIAM_GIA',
            'giaTri' => 100000,
            'donToiThieu' => 500000,
            'ngayBatDau' => '2026-10-01',
            'ngayKetThuc' => '2026-10-31',
            'trangThai' => 'CHUA_BAT_DAU',
        ]);
    }
}
