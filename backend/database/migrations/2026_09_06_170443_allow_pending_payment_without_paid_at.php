<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thanh_toans', fn (Blueprint $table) => $table->dateTime('ngayThanhToan')->nullable()->change());
    }

    public function down(): void
    {
        if (DB::table('thanh_toans')->whereNull('ngayThanhToan')->exists()) {
            throw new RuntimeException('Pending payments have no paid date. Use a forward migration instead of inventing payment dates.');
        }
        Schema::table('thanh_toans', fn (Blueprint $table) => $table->dateTime('ngayThanhToan')->nullable(false)->change());
    }
};
