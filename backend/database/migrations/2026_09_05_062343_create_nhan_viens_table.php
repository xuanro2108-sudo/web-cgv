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
        Schema::create('nhan_viens', function (Blueprint $table) {
        $table->string('maNV')->primary();
        $table->string('hoTen');
        $table->string('sdt');
        $table->string('email');
        $table->string('chucVu');
        $table->date('ngayVaoLam');
        $table->string('trangThai')->default('DANG_LAM');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhan_viens');
    }
};
