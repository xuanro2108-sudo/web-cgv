<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // LichChieu -> Phim
        Schema::table('lich_chieus', function (Blueprint $table) {
            $table->foreign('maPhim')
                ->references('maPhim')
                ->on('phims')
                ->onDelete('cascade');
        });

        // LichChieu -> PhongChieu
        Schema::table('lich_chieus', function (Blueprint $table) {
            $table->foreign('maPhong')
                ->references('maPhong')
                ->on('phong_chieus')
                ->onDelete('cascade');
        });

        // SoDoGhe -> PhongChieu
        Schema::table('so_do_ghes', function (Blueprint $table) {
            $table->foreign('maPhong')
                ->references('maPhong')
                ->on('phong_chieus')
                ->onDelete('cascade');
                $table->unique('maPhong');
        });

        // Ghe -> SoDoGhe
        Schema::table('ghes', function (Blueprint $table) {
            $table->foreign('maSoDo')
                ->references('maSoDo')
                ->on('so_do_ghes')
                ->onDelete('cascade');
        });

        // DonHang -> KhachHang
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->foreign('maKH')
                ->references('maKH')
                ->on('khach_hangs')
                ->onDelete('cascade');
        });

        // DonHang -> NhanVien
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->foreign('maNV')
                ->references('maNV')
                ->on('nhan_viens')
                ->onDelete('set null');
        });

        // DonHang -> KhuyenMai
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->foreign('maKM')
                ->references('maKM')
                ->on('khuyen_mais')
                ->onDelete('set null');
        });

        // VeGhe -> DonHang
        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->foreign('maDonHang')
                ->references('maDonHang')
                ->on('don_hangs')
                ->onDelete('cascade');
        });

        // VeGhe -> LichChieu
        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->foreign('maLichChieu')
                ->references('maLichChieu')
                ->on('lich_chieus')
                ->onDelete('cascade');
        });

        // VeGhe -> Ghe
        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->foreign('maGhe')
                ->references('maGhe')
                ->on('ghes')
                ->onDelete('cascade');

            $table->unique(['maLichChieu', 'maGhe']);
        });

        // ChiTietCombo -> Combo
        Schema::table('chi_tiet_combos', function (Blueprint $table) {
            $table->foreign('maCombo')
                ->references('maCombo')
                ->on('combos')
                ->onDelete('cascade');
        });

        // ChiTietCombo -> SanPham
        Schema::table('chi_tiet_combos', function (Blueprint $table) {
            $table->foreign('maSP')
                ->references('maSP')
                ->on('san_phams')
                ->onDelete('cascade');
        });

        // ChiTietComboDonHang -> DonHang
        Schema::table('chi_tiet_combo_don_hangs', function (Blueprint $table) {
            $table->foreign('maDonHang')
                ->references('maDonHang')
                ->on('don_hangs')
                ->onDelete('cascade');
        });

        // ChiTietComboDonHang -> Combo
        Schema::table('chi_tiet_combo_don_hangs', function (Blueprint $table) {
            $table->foreign('maCombo')
                ->references('maCombo')
                ->on('combos')
                ->onDelete('cascade');
        });

        // ThanhToan -> DonHang
        Schema::table('thanh_toans', function (Blueprint $table) {
            $table->foreign('maDonHang')
                ->references('maDonHang')
                ->on('don_hangs')
                ->onDelete('cascade');

            $table->unique('maDonHang');
        });
    }

    public function down(): void
    {
        Schema::table('thanh_toans', function (Blueprint $table) {
            $table->dropForeign(['maDonHang']);
            $table->dropUnique(['maDonHang']);
        });

        Schema::table('chi_tiet_combo_don_hangs', function (Blueprint $table) {
            $table->dropForeign(['maDonHang']);
            $table->dropForeign(['maCombo']);
        });

        Schema::table('chi_tiet_combos', function (Blueprint $table) {
            $table->dropForeign(['maCombo']);
            $table->dropForeign(['maSP']);
        });

        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->dropForeign(['maDonHang']);
            $table->dropForeign(['maLichChieu']);
            $table->dropForeign(['maGhe']);
            $table->dropUnique(['maLichChieu', 'maGhe']);
        });

        Schema::table('don_hangs', function (Blueprint $table) {
            $table->dropForeign(['maKH']);
            $table->dropForeign(['maNV']);
            $table->dropForeign(['maKM']);
        });

        Schema::table('ghes', function (Blueprint $table) {
            $table->dropForeign(['maSoDo']);
        });

        Schema::table('so_do_ghes', function (Blueprint $table) {
            $table->dropForeign(['maPhong']);
        });

        Schema::table('lich_chieus', function (Blueprint $table) {
            $table->dropForeign(['maPhim']);
            $table->dropForeign(['maPhong']);
        });
    }
};