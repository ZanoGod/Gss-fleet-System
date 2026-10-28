<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_policies', function (Blueprint $table): void {
            $table->unsignedInteger('created_by')->nullable()->change();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
        });
        Schema::table('insurance_claim_events', function (Blueprint $table): void {
            $table->unsignedInteger('changed_by')->nullable()->change();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Audit history requires an approved recovery plan.');
    }
};
