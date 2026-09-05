<?php

namespace Database\Seeders;

use App\Models\SanPham;
use Illuminate\Database\Seeder;

class SanPhamSeeder extends Seeder
{
    public function run(): void
    {
        SanPham::create([
            'maSP' => 'SP001',
            'tenSP' => 'Báº¯p rang bÆ¡',
            'loaiSP' => 'BAP',
            'donGia' => 45000,
            'hinhAnh' => 'bap-rang-bo.jpg',
            'moTa' => 'Báº¯p rang bÆ¡ truyá»n thá»‘ng.',
            'trangThai' => 'HOAT_DONG',
        ]);

        SanPham::create([
            'maSP' => 'SP002',
            'tenSP' => 'Báº¯p rang phĂ´ mai',
            'loaiSP' => 'BAP',
            'donGia' => 50000,
            'hinhAnh' => 'bap-pho-mai.jpg',
            'moTa' => 'Báº¯p rang phá»§ vá»‹ phĂ´ mai.',
            'trangThai' => 'HOAT_DONG',
        ]);

        SanPham::create([
            'maSP' => 'SP003',
            'tenSP' => 'Coca Cola',
            'loaiSP' => 'NUOC',
            'donGia' => 30000,
            'hinhAnh' => 'coca-cola.jpg',
            'moTa' => 'NÆ°á»›c ngá»t Coca Cola.',
            'trangThai' => 'HOAT_DONG',
        ]);

        SanPham::create([
            'maSP' => 'SP004',
            'tenSP' => 'Pepsi',
            'loaiSP' => 'NUOC',
            'donGia' => 30000,
            'hinhAnh' => 'pepsi.jpg',
            'moTa' => 'NÆ°á»›c ngá»t Pepsi.',
            'trangThai' => 'HOAT_DONG',
        ]);

        SanPham::create([
            'maSP' => 'SP005',
            'tenSP' => 'NÆ°á»›c suá»‘i',
            'loaiSP' => 'NUOC',
            'donGia' => 20000,
            'hinhAnh' => 'nuoc-suoi.jpg',
            'moTa' => 'NÆ°á»›c suá»‘i Ä‘Ă³ng chai.',
            'trangThai' => 'HOAT_DONG',
        ]);

        SanPham::create([
            'maSP' => 'SP006',
            'tenSP' => 'Snack khoai tĂ¢y',
            'loaiSP' => 'DO_AN',
            'donGia' => 35000,
            'hinhAnh' => 'snack-khoai-tay.jpg',
            'moTa' => 'Snack khoai tĂ¢y giĂ²n.',
            'trangThai' => 'HOAT_DONG',
        ]);
    }
}
