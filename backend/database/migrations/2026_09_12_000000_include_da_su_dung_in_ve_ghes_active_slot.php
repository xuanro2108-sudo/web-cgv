<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cancel any pending GIU_CHO tickets that conflict with DA_DAT or DA_SU_DUNG
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE ve_ghes v1
                JOIN ve_ghes v2 ON v1.maLichChieu = v2.maLichChieu AND v1.maGhe = v2.maGhe AND v1.maVe != v2.maVe
                SET v1.trangThai = 'DA_HUY'
                WHERE v1.trangThai = 'GIU_CHO' AND v2.trangThai IN ('DA_DAT', 'DA_SU_DUNG')
            ");
        }

        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->dropUnique('ve_ghes_active_unique');
            $table->dropColumn('activeSlot');
        });

        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->unsignedTinyInteger('activeSlot')->nullable()->storedAs("CASE WHEN trangThai IN ('GIU_CHO', 'DA_DAT', 'DA_SU_DUNG') THEN 1 ELSE NULL END");
            $table->unique(['maLichChieu', 'maGhe', 'activeSlot'], 've_ghes_active_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->dropUnique('ve_ghes_active_unique');
            $table->dropColumn('activeSlot');
        });

        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->unsignedTinyInteger('activeSlot')->nullable()->storedAs("CASE WHEN trangThai IN ('GIU_CHO', 'DA_DAT') THEN 1 ELSE NULL END");
            $table->unique(['maLichChieu', 'maGhe', 'activeSlot'], 've_ghes_active_unique');
        });
    }
};

