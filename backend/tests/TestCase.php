<?php

namespace Tests;

use App\Models\Combo;
use App\Models\DonHang;
use App\Models\Ghe;
use App\Models\KhachHang;
use App\Models\LichChieu;
use App\Models\Phim;
use App\Models\PhongChieu;
use App\Models\SoDoGhe;
use App\Models\TaiKhoan;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function seat(): Ghe
    {
        Phim::create(['maPhim' => 'P1', 'tenPhim' => 'Film']);
        PhongChieu::create(['maPhong' => 'R1', 'tenPhong' => 'Room', 'sucChua' => 10, 'trangThai' => 'HOAT_DONG']);
        SoDoGhe::create(['maSoDo' => 'S1', 'maPhong' => 'R1']);
        LichChieu::create(['maLichChieu' => 'L1', 'maPhim' => 'P1', 'maPhong' => 'R1', 'ngayChieu' => today()->addDay(), 'gioBatDau' => '18:00:00', 'gioKetThuc' => '20:00:00', 'giaVeCoBan' => 100000, 'trangThai' => 'HOAT_DONG']);

        return Ghe::create(['maGhe' => 'G1', 'maSoDo' => 'S1', 'hang' => 'A', 'cot' => 1, 'loaiGhe' => 'THUONG', 'trangThai' => 'HOAT_DONG']);
    }

    protected function customer(): TaiKhoan
    {
        $id = (string) Str::ulid();
        $customer = KhachHang::create(['maKH' => 'KH'.$id, 'hoTen' => 'Test customer', 'soDienThoai' => $id, 'email' => $id.'@example.com', 'ngayDangKy' => today(), 'trangThai' => 'HOAT_DONG']);

        return TaiKhoan::create(['maTK' => 'TK'.$id, 'tenDangNhap' => $id, 'matKhau' => Hash::make('password123'), 'vaiTro' => 'KHACH_HANG', 'maKH' => $customer->maKH, 'trangThai' => 'HOAT_DONG']);
    }

    protected function order(TaiKhoan $account): DonHang
    {
        return DonHang::create(['maDonHang' => 'DH'.Str::ulid(), 'maKH' => $account->maKH, 'kieuDat' => 'ONLINE', 'ngayDat' => now(), 'tongTien' => 0, 'trangThai' => 'CHO_THANH_TOAN']);
    }

    protected function combo(): Combo
    {
        return Combo::create(['maCombo' => 'CB'.Str::ulid(), 'tenCombo' => 'Combo', 'donGia' => 70000, 'trangThai' => 'HOAT_DONG']);
    }
}
