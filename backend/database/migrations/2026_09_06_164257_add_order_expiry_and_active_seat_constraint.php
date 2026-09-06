<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('don_hangs', function (Blueprint $table) {
            $table->dateTime('hetHanLuc')->nullable()->index();
        });
        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->index('maLichChieu', 've_ghes_schedule_lookup');
            $table->unsignedTinyInteger('activeSlot')->nullable()->storedAs("CASE WHEN trangThai IN ('GIU_CHO', 'DA_DAT') THEN 1 ELSE NULL END");
        });
        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->dropUnique(['maLichChieu', 'maGhe']);
            $table->unique(['maLichChieu', 'maGhe', 'activeSlot'], 've_ghes_active_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('ve_ghes')->select('maLichChieu', 'maGhe')->groupBy('maLichChieu', 'maGhe')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot restore the old constraint while booking history contains repeated seats. Keep history and use a forward migration.');
        }
        Schema::table('ve_ghes', function (Blueprint $table) {
            $table->dropUnique('ve_ghes_active_unique');
            $table->dropColumn('activeSlot');
            $table->unique(['maLichChieu', 'maGhe']);
        });
        Schema::table('ve_ghes', fn (Blueprint $table) => $table->dropIndex('ve_ghes_schedule_lookup'));
        Schema::table('don_hangs', fn (Blueprint $table) => $table->dropColumn('hetHanLuc'));
    }
};
