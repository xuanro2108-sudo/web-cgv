<?php

namespace Database\Seeders;

use App\Models\KhachHang;
use Illuminate\Database\Seeder;

class KhachHangSeeder extends Seeder
{
    public function run(): void
    {
        KhachHang::create([
            'maKH' => 'KH001',
            'hoTen' => 'Nguyá»…n VÄƒn An',
            'soDienThoai' => '0901234567',
            'email' => 'nguyenan@gmail.com',
            'ngaySinh' => '2000-05-15',
            'gioiTinh' => 'NAM',
            'ngayDangKy' => '2026-08-01',
            'trangThai' => 'HOAT_DONG',
        ]);

        KhachHang::create([
            'maKH' => 'KH002',
            'hoTen' => 'Tráº§n Thá»‹ BĂ¬nh',
            'soDienThoai' => '0912345678',
            'email' => 'tranbinh@gmail.com',
            'ngaySinh' => '2001-09-20',
            'gioiTinh' => 'NU',
            'ngayDangKy' => '2026-08-05',
            'trangThai' => 'HOAT_DONG',
        ]);

        KhachHang::create([
            'maKH' => 'KH003',
            'hoTen' => 'LĂª Minh Khang',
            'soDienThoai' => '0923456789',
            'email' => 'lekhang@gmail.com',
            'ngaySinh' => '1999-12-10',
            'gioiTinh' => 'NAM',
            'ngayDangKy' => '2026-08-10',
            'trangThai' => 'HOAT_DONG',
        ]);

        KhachHang::create([
            'maKH' => 'KH004',
            'hoTen' => 'Pháº¡m Ngá»c Anh',
            'soDienThoai' => '0934567890',
            'email' => 'phamngocanh@gmail.com',
            'ngaySinh' => '2002-03-25',
            'gioiTinh' => 'NU',
            'ngayDangKy' => '2026-08-15',
            'trangThai' => 'HOAT_DONG',
        ]);

        KhachHang::create([
            'maKH' => 'KH005',
            'hoTen' => 'HoĂ ng Quá»‘c Viá»‡t',
            'soDienThoai' => '0945678901',
            'email' => 'hoangviet@gmail.com',
            'ngaySinh' => '1998-07-08',
            'gioiTinh' => 'NAM',
            'ngayDangKy' => '2026-08-20',
            'trangThai' => 'HOAT_DONG',
        ]);
    }
}
