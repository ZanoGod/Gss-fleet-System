<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('monthly_contract_sequences')) {
            Schema::create('monthly_contract_sequences', function (Blueprint $table): void {
                $table->string('month_key', 6)->primary();
                $table->unsignedInteger('next_value')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Automatic rollback is disabled for contract number allocation. Use an approved recovery plan.');
    }
};
