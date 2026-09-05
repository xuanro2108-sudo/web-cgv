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
        Schema::create('don_hangs', function (Blueprint $table) {
        $table->string('maDonHang')->primary();
        $table->string('maKH');
        $table->string('maNV')->nullable();
        $table->string('maKM')->nullable();
        $table->string('kieuDat');
        $table->dateTime('ngayDat');
        $table->decimal('tongTien', 12, 2)->default(0);
        $table->string('maQR')->nullable();
        $table->string('trangThai')->default('CHO_THANH_TOAN');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('don_hangs');
    }
};
