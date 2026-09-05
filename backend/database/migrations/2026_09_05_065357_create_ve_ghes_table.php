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
        Schema::create('ve_ghes', function (Blueprint $table) {
        $table->string('maVe')->primary();
        $table->string('maDonHang');
        $table->string('maLichChieu');
        $table->string('maGhe');
        $table->decimal('giaVe', 10, 2);
        $table->string('trangThai')->default('TRONG');
        $table->dateTime('ngayTao');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ve_ghes');
    }
};
