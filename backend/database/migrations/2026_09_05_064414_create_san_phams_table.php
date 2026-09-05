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
        Schema::create('san_phams', function (Blueprint $table) {
        $table->string('maSP')->primary();
        $table->string('tenSP');
        $table->string('loaiSP');
        $table->decimal('donGia', 10, 2);
        $table->string('hinhAnh')->nullable();
        $table->text('moTa')->nullable();
        $table->string('trangThai')->default('HOAT_DONG');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('san_phams');
    }
};
