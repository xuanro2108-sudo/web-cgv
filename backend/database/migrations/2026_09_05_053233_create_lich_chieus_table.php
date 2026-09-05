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
        Schema::create('lich_chieus', function (Blueprint $table) {
         $table->string('maLichChieu')->primary();
        $table->string('maPhim');
        $table->string('maPhong');
        $table->date('ngayChieu');
        $table->time('gioBatDau');
        $table->time('gioKetThuc');
        $table->decimal('giaVeCoBan', 10, 2);
        $table->string('trangThai')->default('HOAT_DONG');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_chieus');
    }
};
