<?php

namespace Database\Seeders;

use App\Models\Phim;
use Illuminate\Database\Seeder;

class PhimSeeder extends Seeder
{
    public function run(): void
    {
        Phim::create([
            'maPhim' => 'PHIM001',
            'tenPhim' => 'Avengers: Endgame',
            'theLoai' => 'Hành động, Viễn tưởng',
            'thoiLuong' => 181,
            'daoDien' => 'Anthony Russo, Joe Russo',
            'dienVien' => 'Robert Downey Jr., Chris Evans, Mark Ruffalo',
            'ngayKhoiChieu' => '2019-04-26',
            'ngayKetThuc' => '2019-06-30',
            'moTa' => 'Các siêu anh hùng cùng nhau chống lại Thanos và khôi phục vũ trụ.',
            'hinhAnh' => 'avengers-endgame.jpg',
            'trailer' => 'https://www.youtube.com/',
            'trangThai' => 'DA_CHIEU',
        ]);

        Phim::create([
            'maPhim' => 'PHIM002',
            'tenPhim' => 'Dune: Part Two',
            'theLoai' => 'Khoa học viễn tưởng, Phiêu lưu',
            'thoiLuong' => 166,
            'daoDien' => 'Denis Villeneuve',
            'dienVien' => 'Timothée Chalamet, Zendaya, Rebecca Ferguson',
            'ngayKhoiChieu' => '2024-03-01',
            'ngayKetThuc' => '2024-05-31',
            'moTa' => 'Paul Atreides tiếp tục hành trình cùng Chani và người Fremen.',
            'hinhAnh' => 'dune-part-two.jpg',
            'trailer' => 'https://www.youtube.com/',
            'trangThai' => 'DA_CHIEU',
        ]);

        Phim::create([
            'maPhim' => 'PHIM003',
            'tenPhim' => 'Inside Out 2',
            'theLoai' => 'Hoạt hình, Hài',
            'thoiLuong' => 96,
            'daoDien' => 'Kelsey Mann',
            'dienVien' => 'Amy Poehler, Maya Hawke, Kensington Tallman',
            'ngayKhoiChieu' => '2024-06-14',
            'ngayKetThuc' => '2024-08-31',
            'moTa' => 'Riley bước vào tuổi thiếu niên và trải qua những cảm xúc mới.',
            'hinhAnh' => 'inside-out-2.jpg',
            'trailer' => 'https://www.youtube.com/',
            'trangThai' => 'DA_CHIEU',
        ]);

        Phim::create([
            'maPhim' => 'PHIM004',
            'tenPhim' => 'Avatar 3',
            'theLoai' => 'Khoa học viễn tưởng, Phiêu lưu',
            'thoiLuong' => 180,
            'daoDien' => 'James Cameron',
            'dienVien' => 'Sam Worthington, Zoe Saldana',
            'ngayKhoiChieu' => '2026-12-18',
            'ngayKetThuc' => null,
            'moTa' => 'Phần tiếp theo của hành trình khám phá thế giới Pandora.',
            'hinhAnh' => 'avatar-3.jpg',
            'trailer' => 'https://www.youtube.com/',
            'trangThai' => 'SAP_CHIEU',
        ]);
    }
}