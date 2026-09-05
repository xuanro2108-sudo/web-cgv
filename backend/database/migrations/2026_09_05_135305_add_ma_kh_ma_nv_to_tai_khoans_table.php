<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tai_khoans', function (Blueprint $table) {
            $table->string('maKH')->nullable()->after('vaiTro');
            $table->string('maNV')->nullable()->after('maKH');

            $table->foreign('maKH')
                ->references('maKH')
                ->on('khach_hangs')
                ->onDelete('cascade');

            $table->foreign('maNV')
                ->references('maNV')
                ->on('nhan_viens')
                ->onDelete('cascade');

            $table->unique('maKH');
            $table->unique('maNV');
        });
    }

    public function down(): void
    {
        Schema::table('tai_khoans', function (Blueprint $table) {
            $table->dropForeign(['maKH']);
            $table->dropForeign(['maNV']);

            $table->dropUnique(['maKH']);
            $table->dropUnique(['maNV']);

            $table->dropColumn(['maKH', 'maNV']);
        });
    }
};