<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('car_id')->nullable();
            $table->unsignedInteger('monthly_car_id')->nullable();
            $table->foreign('car_id')->references('id')->on('cars')->restrictOnDelete();
            $table->foreign('monthly_car_id')->references('id')->on('monthly_cars')->restrictOnDelete();
            $table->string('maintenance_type', 30);
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('scheduled_date');
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->unsignedInteger('odometer_reading')->nullable();
            $table->string('service_provider', 200)->nullable();
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->char('currency', 3);
            $table->string('status', 30)->default('Scheduled');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'scheduled_date']);
        });
        // Both references have real foreign keys. Exactly one must be supplied.
        $sql = 'ALTER TABLE vehicle_maintenances ADD CONSTRAINT maintenance_one_vehicle CHECK ((car_id IS NOT NULL AND monthly_car_id IS NULL) OR (car_id IS NULL AND monthly_car_id IS NOT NULL))';
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement($sql);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
    }
};
