<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PhimSeeder::class,
            PhongChieuSeeder::class,
            SoDoGheSeeder::class,
            GheSeeder::class,
            LichChieuSeeder::class,
            KhachHangSeeder::class,
            NhanVienSeeder::class,
            TaiKhoanSeeder::class,
            KhuyenMaiSeeder::class,
            SanPhamSeeder::class,
            ComboSeeder::class,
            ChiTietComboSeeder::class,
            DonHangSeeder::class,
            VeGheSeeder::class,
            ChiTietComboDonHangSeeder::class,
            ThanhToanSeeder::class,
        ]);
    }
}
