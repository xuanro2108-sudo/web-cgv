<?php

namespace Database\Seeders;

use App\Models\NhanVien;
use Illuminate\Database\Seeder;

class NhanVienSeeder extends Seeder
{
    public function run(): void
    {
        NhanVien::create([
            'maNV' => 'NV001',
            'hoTen' => 'Nguyá»…n VÄƒn Minh',
            'sdt' => '0901111111',
            'email' => 'nguyenminh@cgv.vn',
            'chucVu' => 'QUAN_LY',
            'ngayVaoLam' => '2025-01-10',
            'trangThai' => 'DANG_LAM',
        ]);

        NhanVien::create([
            'maNV' => 'NV002',
            'hoTen' => 'Tráº§n Quá»‘c Huy',
            'sdt' => '0902222222',
            'email' => 'tranhuy@cgv.vn',
            'chucVu' => 'NHAN_VIEN',
            'ngayVaoLam' => '2025-03-15',
            'trangThai' => 'DANG_LAM',
        ]);

        NhanVien::create([
            'maNV' => 'NV003',
            'hoTen' => 'LĂª Thá»‹ Mai',
            'sdt' => '0903333333',
            'email' => 'lemai@cgv.vn',
            'chucVu' => 'NHAN_VIEN',
            'ngayVaoLam' => '2025-06-01',
            'trangThai' => 'DANG_LAM',
        ]);

        NhanVien::create([
            'maNV' => 'NV004',
            'hoTen' => 'Pháº¡m HoĂ ng Nam',
            'sdt' => '0904444444',
            'email' => 'phamnam@cgv.vn',
            'chucVu' => 'NHAN_VIEN',
            'ngayVaoLam' => '2025-08-20',
            'trangThai' => 'DANG_LAM',
        ]);
    }
}
