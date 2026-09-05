<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chi_tiet_combo_don_hangs', function (Blueprint $table) {
        $table->string('maChiTiet')->primary();
        $table->string('maDonHang');
        $table->string('maCombo');
        $table->integer('soLuong');
        $table->decimal('donGia', 10, 2);
        $table->decimal('thanhTien', 12, 2);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_combo_don_hangs');
    }
};
