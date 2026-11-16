<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cars', 'monthly_cars', 'users'] as $table) {
            if (! Schema::hasTable($table)) throw new RuntimeException("Car licenses require the {$table} table.");
        }
        Schema::create('car_licenses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('car_id')->nullable();
            $table->unsignedInteger('monthly_car_id')->nullable();
            $table->foreign('car_id')->references('id')->on('cars')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('monthly_car_id')->references('id')->on('monthly_cars')->restrictOnDelete()->cascadeOnUpdate();
            $table->string('license_number', 100);
            $table->index('license_number');
            $table->unsignedTinyInteger('license_period');
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->date('renewal_date')->nullable();
            $table->decimal('renewal_fee', 15, 2)->default(0);
            $table->string('office_region', 100);
            $table->string('office_branch', 150);
            $table->text('remarks')->nullable();
            $table->foreignId('renewal_parent_id')->nullable()->unique()->constrained('car_licenses')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['expiry_date', 'car_id']);
            $table->index(['expiry_date', 'monthly_car_id']);
        });
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE car_licenses ADD CONSTRAINT car_license_one_vehicle CHECK ((car_id IS NOT NULL AND monthly_car_id IS NULL) OR (car_id IS NULL AND monthly_car_id IS NOT NULL))');
            DB::statement('ALTER TABLE car_licenses ADD CONSTRAINT car_license_valid_term CHECK (license_period IN (1,2,3) AND expiry_date >= issue_date AND renewal_fee >= 0)');
        }
        Schema::create('car_license_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('car_license_id')->constrained('car_licenses')->cascadeOnDelete();
            $table->string('storage_key')->unique();
            $table->string('original_name', 200);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_license_documents');
        Schema::dropIfExists('car_licenses');
    }
};
