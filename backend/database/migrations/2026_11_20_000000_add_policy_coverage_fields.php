<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_policies', function (Blueprint $table): void {
            $table->decimal('windscreen_amount', 14, 2)->default(0)->after('coverage_amount');
            $table->decimal('no_claim_bonus', 14, 2)->default(0)->after('windscreen_amount');
            $table->boolean('betterment_cover')->default(false)->after('no_claim_bonus');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_policies', function (Blueprint $table): void {
            $table->dropColumn(['windscreen_amount', 'no_claim_bonus', 'betterment_cover']);
        });
    }
};
