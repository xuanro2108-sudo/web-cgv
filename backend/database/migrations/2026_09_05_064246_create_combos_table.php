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
        Schema::create('combos', function (Blueprint $table) {
        $table->string('maCombo')->primary();
        $table->string('tenCombo');
        $table->decimal('donGia', 10, 2);
        $table->text('moTa')->nullable();
        $table->string('hinhAnh')->nullable();
        $table->string('trangThai')->default('HOAT_DONG');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combos');
    }
};
