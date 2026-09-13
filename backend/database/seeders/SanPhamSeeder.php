<?php

namespace Database\Seeders;

use App\Models\SanPham;
use Illuminate\Database\Seeder;

class SanPhamSeeder extends Seeder
{
    public function run(): void
    {
        SanPham::updateOrCreate(
            ['maSP' => 'SP001'],
            [
                'tenSP' => 'Bắp rang bơ',
                'loaiSP' => 'BAP',
                'donGia' => 45000,
                'hinhAnh' => 'bap-rang-bo.jpg',
                'moTa' => 'Bắp rang bơ truyền thống.',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        SanPham::updateOrCreate(
            ['maSP' => 'SP002'],
            [
                'tenSP' => 'Bắp rang phô mai',
                'loaiSP' => 'BAP',
                'donGia' => 50000,
                'hinhAnh' => 'bap-pho-mai.jpg',
                'moTa' => 'Bắp rang phủ vị phô mai.',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        SanPham::updateOrCreate(
            ['maSP' => 'SP003'],
            [
                'tenSP' => 'Coca Cola',
                'loaiSP' => 'NUOC',
                'donGia' => 30000,
                'hinhAnh' => 'coca-cola.jpg',
                'moTa' => 'Nước ngọt Coca Cola.',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        SanPham::updateOrCreate(
            ['maSP' => 'SP004'],
            [
                'tenSP' => 'Pepsi',
                'loaiSP' => 'NUOC',
                'donGia' => 30000,
                'hinhAnh' => 'pepsi.jpg',
                'moTa' => 'Nước ngọt Pepsi.',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        SanPham::updateOrCreate(
            ['maSP' => 'SP005'],
            [
                'tenSP' => 'Nước suối',
                'loaiSP' => 'NUOC',
                'donGia' => 20000,
                'hinhAnh' => 'nuoc-suoi.jpg',
                'moTa' => 'Nước suối đóng chai.',
                'trangThai' => 'HOAT_DONG',
            ]
        );

        SanPham::updateOrCreate(
            ['maSP' => 'SP006'],
            [
                'tenSP' => 'Snack khoai tây',
                'loaiSP' => 'DO_AN',
                'donGia' => 35000,
                'hinhAnh' => 'snack-khoai-tay.jpg',
                'moTa' => 'Snack khoai tây giòn.',
                'trangThai' => 'HOAT_DONG',
            ]
        );
    }
}
