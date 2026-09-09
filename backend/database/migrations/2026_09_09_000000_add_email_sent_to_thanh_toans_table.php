<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thanh_toans', function (Blueprint $table): void {
            $table->boolean('emailDaGui')->default(false)->after('trangThai');
        });
    }

    public function down(): void
    {
        Schema::table('thanh_toans', function (Blueprint $table): void {
            $table->dropColumn('emailDaGui');
        });
    }
};
