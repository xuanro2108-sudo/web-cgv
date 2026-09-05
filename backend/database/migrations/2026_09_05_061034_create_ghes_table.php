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
        Schema::create('ghes', function (Blueprint $table) {
    $table->string('maGhe')->primary();
    $table->string('maSoDo');
    $table->string('hang');
    $table->integer('cot');
    $table->string('loaiGhe');
    $table->string('trangThai')->default('HOAT_DONG');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ghes');
    }
};
