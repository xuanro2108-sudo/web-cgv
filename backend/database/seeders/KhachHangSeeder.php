<?php

namespace Database\Seeders;

use App\Models\KhachHang;
use Illuminate\Database\Seeder;

class KhachHangSeeder extends Seeder
{
    public function run(): void
    {
        KhachHang::updateOrCreate(
            ['maKH' => 'KH001'],
            [
                'hoTen' => 'Nguyễn Văn An',
                'soDienThoai' => '0901234567',
                'email' => 'nguyenan@gmail.com',
                'ngaySinh' => '2000-05-15',
                'gioiTinh' => 'NAM',
                'ngayDangKy' => '2026-08-01',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        KhachHang::updateOrCreate(
            ['maKH' => 'KH002'],
            [
                'hoTen' => 'Trần Thị Bình',
                'soDienThoai' => '0912345678',
                'email' => 'tranbinh@gmail.com',
                'ngaySinh' => '2001-09-20',
                'gioiTinh' => 'NU',
                'ngayDangKy' => '2026-08-05',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        KhachHang::updateOrCreate(
            ['maKH' => 'KH003'],
            [
                'hoTen' => 'Lê Minh Khang',
                'soDienThoai' => '0923456789',
                'email' => 'lekhang@gmail.com',
                'ngaySinh' => '1999-12-10',
                'gioiTinh' => 'NAM',
                'ngayDangKy' => '2026-08-10',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        KhachHang::updateOrCreate(
            ['maKH' => 'KH004'],
            [
                'hoTen' => 'Phạm Ngọc Anh',
                'soDienThoai' => '0934567890',
                'email' => 'phamngocanh@gmail.com',
                'ngaySinh' => '2002-03-25',
                'gioiTinh' => 'NU',
                'ngayDangKy' => '2026-08-15',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        KhachHang::updateOrCreate(
            ['maKH' => 'KH005'],
            [
                'hoTen' => 'Hoàng Quốc Việt',
                'soDienThoai' => '0945678901',
                'email' => 'hoangviet@gmail.com',
                'ngaySinh' => '1998-07-08',
                'gioiTinh' => 'NAM',
                'ngayDangKy' => '2026-08-20',
                'trangThai' => 'HOAT_DONG',
            ]
        );
    }
}