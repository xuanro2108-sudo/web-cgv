<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Phim
        $this->call(PhimSeeder::class);

        // 2. PhĂ²ng chiáº¿u
        $this->call(PhongChieuSeeder::class);

        // 3. SÆ¡ Ä‘á»“ gháº¿
        $this->call(SoDoGheSeeder::class);

        // 4. Gháº¿
        $this->call(GheSeeder::class);

        // 5. Lá»‹ch chiáº¿u
        $this->call(LichChieuSeeder::class);

        // 6. KhĂ¡ch hĂ ng
        $this->call(KhachHangSeeder::class);

        // 7. NhĂ¢n viĂªn
        $this->call(NhanVienSeeder::class);

        // 8. TĂ i khoáº£n
        $this->call(TaiKhoanSeeder::class);

        // 9. Khuyáº¿n mĂ£i
        $this->call(KhuyenMaiSeeder::class);

        // 10. Sáº£n pháº©m
        $this->call(SanPhamSeeder::class);

        // 11. Combo
        $this->call(ComboSeeder::class);

        // 12. Chi tiáº¿t combo
        $this->call(ChiTietComboSeeder::class);

        // 13. ÄÆ¡n hĂ ng
        $this->call(DonHangSeeder::class);

        // 14. VĂ© gháº¿
        $this->call(VeGheSeeder::class);

        // 15. Chi tiáº¿t combo Ä‘Æ¡n hĂ ng
        $this->call(ChiTietComboDonHangSeeder::class);

        // 16. Thanh toĂ¡n
        $this->call(ThanhToanSeeder::class);
    }
}
