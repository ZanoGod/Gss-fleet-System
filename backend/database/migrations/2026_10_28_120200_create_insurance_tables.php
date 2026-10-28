<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'insurance_providers', 'insurance_policies', 'insurance_claims',
        'insurance_claim_events', 'insurance_documents',
    ];

    public function up(): void
    {
        $present = array_values(array_filter($this->tables, fn (string $table): bool => Schema::hasTable($table)));
        if ($present !== []) {
            throw new RuntimeException(
                'Insurance migration stopped because tables already exist: '.implode(', ', $present).
                '. Compare their live definitions and Laravel migration history before continuing.'
            );
        }

        foreach (['admins', 'cars', 'monthly_cars'] as $required) {
            if (! Schema::hasTable($required)) {
                throw new RuntimeException("Insurance requires the verified legacy {$required} table.");
            }
        }

        Schema::create('insurance_providers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_policies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('car_id')->nullable();
            $table->unsignedInteger('monthly_car_id')->nullable();
            $table->foreign('car_id')->references('id')->on('cars')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('monthly_car_id')->references('id')->on('monthly_cars')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('provider_id')->constrained('insurance_providers')->restrictOnDelete();
            $table->string('policy_number', 100);
            $table->string('insurance_type', 80);
            $table->text('coverage_details')->nullable();
            $table->date('start_date');
            $table->date('expiration_date');
            $table->decimal('premium_amount', 14, 2);
            $table->string('currency', 3);
            $table->decimal('coverage_amount', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('renewal_parent_id')->nullable()->unique()
                ->constrained('insurance_policies')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedInteger('created_by');
            $table->foreign('created_by')->references('id')->on('admins')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['provider_id', 'policy_number', 'start_date'], 'insurance_policy_term_unique');
            $table->index(['car_id', 'insurance_type', 'expiration_date'], 'insurance_car_dates');
            $table->index(['monthly_car_id', 'insurance_type', 'expiration_date'], 'insurance_monthly_car_dates');
            $table->index(['expiration_date', 'cancelled_at', 'archived_at'], 'insurance_due_dates');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE insurance_policies ADD CONSTRAINT insurance_one_vehicle CHECK ((car_id IS NOT NULL) + (monthly_car_id IS NOT NULL) = 1)');
            DB::statement("ALTER TABLE insurance_policies ADD CONSTRAINT insurance_valid_term CHECK (expiration_date >= start_date AND premium_amount >= 0 AND coverage_amount >= 0 AND currency IN ('MMK','USD'))");
        }

        Schema::create('insurance_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('policy_id')->constrained('insurance_policies')->restrictOnDelete();
            $table->date('incident_date');
            $table->string('reference_number', 100)->unique();
            $table->decimal('amount', 14, 2);
            $table->string('status', 30);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('insurance_claim_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('claim_id')->constrained('insurance_claims')->restrictOnDelete();
            $table->string('status', 30);
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->unsignedInteger('changed_by');
            $table->foreign('changed_by')->references('id')->on('admins')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('insurance_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('policy_id')->constrained('insurance_policies')->restrictOnDelete();
            $table->string('storage_key')->unique();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('replaces_id')->nullable()->unique()
                ->constrained('insurance_documents')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Automatic rollback is disabled for insurance history and documents. Use an approved backup/restore plan.');
    }
};
