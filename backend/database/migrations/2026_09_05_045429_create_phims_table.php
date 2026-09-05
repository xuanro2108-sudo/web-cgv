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
            Schema::create('phims', function (Blueprint $table) {
            $table->string('maPhim')->primary();
            $table->string('tenPhim');
            $table->string('theLoai')->nullable();
            $table->integer('thoiLuong')->nullable();
            $table->string('daoDien')->nullable();
            $table->string('dienVien')->nullable();
            $table->date('ngayKhoiChieu')->nullable();
            $table->date('ngayKetThuc')->nullable();
            $table->text('moTa')->nullable();
            $table->string('hinhAnh')->nullable();
            $table->string('trailer')->nullable();
            $table->string('trangThai')->default('SAP_CHIEU');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phims');
    }
};
