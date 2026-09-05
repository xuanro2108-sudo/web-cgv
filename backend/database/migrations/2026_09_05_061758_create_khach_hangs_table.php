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
        Schema::create('khach_hangs', function (Blueprint $table) {
    $table->string('maKH')->primary();
    $table->string('hoTen');
    $table->string('soDienThoai');
    $table->string('email');
    $table->date('ngaySinh')->nullable();
    $table->string('gioiTinh')->nullable();
    $table->date('ngayDangKy');
    $table->string('trangThai')->default('HOAT_DONG');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('khach_hangs');
    }
};
