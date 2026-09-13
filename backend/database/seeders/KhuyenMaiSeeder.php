<?php

namespace Database\Seeders;

use App\Models\KhuyenMai;
use Illuminate\Database\Seeder;

class KhuyenMaiSeeder extends Seeder
{
    public function run(): void
    {
        KhuyenMai::updateOrCreate(
            ['maKM' => 'KM001'],
            [
                'tenKM' => 'Giảm 20% cho khách hàng mới',
                'hinhThuc' => 'GIAM_PHAN_TRAM',
                'giaTri' => 20,
                'donToiThieu' => 100000,
                'ngayBatDau' => '2026-09-01',
                'ngayKetThuc' => '2026-09-30',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        KhuyenMai::updateOrCreate(
            ['maKM' => 'KM002'],
            [
                'tenKM' => 'Giảm 50.000đ cho đơn từ 300.000đ',
                'hinhThuc' => 'GIAM_GIA',
                'giaTri' => 50000,
                'donToiThieu' => 300000,
                'ngayBatDau' => '2026-09-01',
                'ngayKetThuc' => '2026-09-30',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        KhuyenMai::updateOrCreate(
            ['maKM' => 'KM003'],
            [
                'tenKM' => 'Ưu đãi cuối tuần',
                'hinhThuc' => 'GIAM_PHAN_TRAM',
                'giaTri' => 15,
                'donToiThieu' => 150000,
                'ngayBatDau' => '2026-09-01',
                'ngayKetThuc' => '2026-12-31',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        KhuyenMai::updateOrCreate(
            ['maKM' => 'KM004'],
            [
                'tenKM' => 'Khuyến mãi đặc biệt',
                'hinhThuc' => 'GIAM_GIA',
                'giaTri' => 100000,
                'donToiThieu' => 500000,
                'ngayBatDau' => '2026-10-01',
                'ngayKetThuc' => '2026-10-31',
                'trangThai' => 'CHUA_BAT_DAU',
            ]
        );
    }
}
