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
        Schema::create('phong_chieus', function (Blueprint $table) {
            $table->string('maPhong')->primary();
            $table->string('tenPhong');
            $table->integer('sucChua');
            $table->string('trangThai')->default('HOAT_DONG');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phong_chieus');
    }
};
