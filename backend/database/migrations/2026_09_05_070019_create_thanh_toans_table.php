<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

        public function up(): void
{
    Schema::create('thanh_toans', function (Blueprint $table) {
        $table->string('maTT')->primary();
        $table->string('maDonHang');
        $table->string('maGiaoDich');
        $table->decimal('soTien', 12, 2);
        $table->string('phuongThuc');
        $table->dateTime('ngayThanhToan');
        $table->string('trangThai')->default('CHO_THANH_TOAN');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thanh_toans');
    }
};
