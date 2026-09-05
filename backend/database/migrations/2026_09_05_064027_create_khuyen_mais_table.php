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
        Schema::create('khuyen_mais', function (Blueprint $table) {
        $table->string('maKM')->primary();
        $table->string('tenKM');
        $table->string('hinhThuc');
        $table->decimal('giaTri', 10, 2);
        $table->decimal('donToiThieu', 10, 2)->default(0);
        $table->date('ngayBatDau');
        $table->date('ngayKetThuc');
        $table->string('trangThai')->default('HOAT_DONG');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('khuyen_mais');
    }
};
