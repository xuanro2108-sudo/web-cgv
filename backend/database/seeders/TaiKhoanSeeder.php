<?php

namespace Database\Seeders;

use App\Models\TaiKhoan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TaiKhoanSeeder extends Seeder
{
    public function run(): void
    {
        // TĂ i khoáº£n khĂ¡ch hĂ ng
        TaiKhoan::create([
            'maTK' => 'TK001',
            'tenDangNhap' => 'nguyenan',
            'matKhau' => Hash::make('123456'),
            'vaiTro' => 'KHACH_HANG',
            'maKH' => 'KH001',
            'maNV' => null,
            'trangThai' => 'HOAT_DONG',
        ]);

        TaiKhoan::create([
            'maTK' => 'TK002',
            'tenDangNhap' => 'tranbinh',
          'matKhau' => Hash::make('123456'),
            'vaiTro' => 'KHACH_HANG',
            'maKH' => 'KH002',
            'maNV' => null,
            'trangThai' => 'HOAT_DONG',
        ]);

        // TĂ i khoáº£n quáº£n lĂ½
       TaiKhoan::create([
    'maTK' => 'TK003',
    'tenDangNhap' => 'admin@gmail.com',
    'matKhau' => Hash::make('123456'),
    'vaiTro' => 'QUAN_LY',
    'maKH' => null,
    'maNV' => 'NV001',
    'trangThai' => 'HOAT_DONG',
]);

        // TĂ i khoáº£n nhĂ¢n viĂªn
        TaiKhoan::create([
            'maTK' => 'TK004',
            'tenDangNhap' => 'nhanvien01',
           'matKhau' => Hash::make('123456'),
            'vaiTro' => 'NHAN_VIEN',
            'maKH' => null,
            'maNV' => 'NV002',
            'trangThai' => 'HOAT_DONG',
        ]);

        TaiKhoan::create([
            'maTK' => 'TK005',
            'tenDangNhap' => 'nhanvien02',
        'matKhau' => Hash::make('123456'),
            'vaiTro' => 'NHAN_VIEN',
            'maKH' => null,
            'maNV' => 'NV003',
            'trangThai' => 'HOAT_DONG',
        ]);
    }
}
