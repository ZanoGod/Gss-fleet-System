<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_licenses', function (Blueprint $table): void {
            $table->string('license_number', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('car_licenses')->whereNull('license_number')->update(['license_number' => '']);
        Schema::table('car_licenses', function (Blueprint $table): void {
            $table->string('license_number', 100)->nullable(false)->change();
        });
    }
};
