<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'admins', 'cars', 'customers', 'drivers', 'monthly_cars',
        'monthly_drivers', 'operators', 'bookings', 'monthly_contracts',
    ];

    public function up(): void
    {
        $present = array_values(array_filter($this->tables, fn (string $table): bool => Schema::hasTable($table)));

        if ($present !== []) {
            throw new RuntimeException(
                'Legacy fleet migration stopped because domain tables already exist: '.implode(', ', $present).
                '. Compare their live definitions with DATABASE_MAPPING.md and create a reviewed compatibility migration.'
            );
        }

        Schema::create('admins', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('username', 80)->unique('idx_admins_username');
            $table->string('email', 150)->nullable()->unique();
            $table->string('password_hash', 255);
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_login_at')->nullable();
            $this->legacyTimestamps($table);
        });

        Schema::create('cars', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('car_type', 100);
            $table->string('plate_no', 50)->unique();
            $table->string('model_name', 100);
            $table->unsignedTinyInteger('seat_capacity')->default(4);
            $table->enum('availability_status', ['Available', 'Assigned', 'Maintenance'])->default('Available');
            $table->text('note')->nullable();
            $this->legacyTimestamps($table);
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('company_name')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->text('customer_address')->nullable();
            $table->string('off_day', 50)->nullable();
            $table->string('service_hour', 100)->nullable();
            $table->enum('customer_status', ['Active', 'Inactive'])->default('Active');
            $table->text('remark')->nullable();
            $this->legacyTimestamps($table);
        });

        Schema::create('drivers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('full_name', 150);
            $table->string('phone_number', 30);
            $table->string('license_no', 100)->nullable()->unique();
            $table->enum('driver_status', ['Available', 'Assigned', 'Leave', 'Inactive'])->default('Available');
            $table->text('note')->nullable();
            $this->legacyTimestamps($table);
        });

        Schema::create('monthly_cars', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('car_type', 100);
            $table->string('plate_no', 50)->unique();
            $table->string('model_name', 100);
            $table->unsignedTinyInteger('seat_capacity')->default(4);
            $table->enum('availability_status', ['Available', 'Assigned', 'Maintenance', 'Inactive'])->default('Available');
            $table->text('note')->nullable();
            $this->legacyTimestamps($table);
        });

        Schema::create('monthly_drivers', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('full_name', 150);
            $table->string('phone_number', 30);
            $table->string('license_no', 100)->nullable()->unique();
            $table->enum('driver_status', ['Available', 'Assigned', 'Leave', 'Inactive'])->default('Available');
            $table->text('note')->nullable();
            $this->legacyTimestamps($table);
        });

        Schema::create('operators', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('full_name', 150)->unique();
            $table->string('phone_number', 30)->nullable();
            $table->enum('operator_status', ['Active', 'Inactive'])->default('Active');
            $table->text('note')->nullable();
            $this->legacyTimestamps($table);
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('guest_company_name');
            $table->unsignedInteger('car_id')->nullable();
            $table->string('custom_car_name')->nullable();
            $table->unsignedInteger('secondary_car_id')->nullable();
            $table->unsignedInteger('driver_id')->nullable();
            $table->string('custom_driver_name')->nullable();
            $table->unsignedInteger('operator_id')->nullable();
            $table->string('operator_name', 150);
            $table->enum('even_odd', ['Even', 'Odd'])->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['Pending', 'Confirm', 'Completed', 'Cancelled'])->default('Pending');
            $table->text('remark')->nullable();
            $this->legacyTimestamps($table);

            $table->index(['status', 'start_date', 'end_date'], 'idx_bookings_status_dates');
            $table->index('car_id', 'idx_bookings_car_id');
            $table->index('secondary_car_id', 'idx_bookings_secondary_car_id');
            $table->index('driver_id', 'idx_bookings_driver_id');
            $table->index('operator_id', 'idx_bookings_operator_id');
            $table->foreign('car_id')->references('id')->on('cars')->cascadeOnUpdate();
            $table->foreign('secondary_car_id')->references('id')->on('cars')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('driver_id')->references('id')->on('drivers')->cascadeOnUpdate();
            $table->foreign('operator_id')->references('id')->on('operators')->cascadeOnUpdate();
        });

        Schema::create('monthly_contracts', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('contract_no', 50)->unique();
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('customer_name');
            $table->string('guest_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->text('customer_address')->nullable();
            $table->enum('assignment_type', ['Driver Only', 'Vehicle Only', 'Vehicle and Driver'])->default('Vehicle Only');
            $table->unsignedInteger('car_id')->nullable();
            $table->unsignedInteger('driver_id')->nullable();
            $table->unsignedInteger('operator_id')->nullable();
            $table->string('operator_name', 150)->nullable();
            $table->enum('even_odd', ['Even', 'Odd'])->nullable();
            $table->string('operation_sheet')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rate', 12, 2)->default(0);
            $table->enum('rental_currency', ['MMK', 'USD'])->default('MMK');
            $table->string('rental_status', 50);
            $table->enum('status', ['Draft', 'Active', 'Expired', 'Renewed', 'Cancelled'])->default('Active');
            $table->unsignedInteger('renewal_parent_id')->nullable();
            $table->text('note')->nullable();
            $table->string('prepared_by', 150)->nullable();
            $table->string('invoice_sender', 150)->nullable();
            $table->string('checked_by', 150)->nullable();
            $table->string('off_day', 100)->nullable();
            $table->string('service_hour', 100)->nullable();
            $table->decimal('insurance_price', 12, 2)->default(0);
            $table->string('insurance_company')->nullable();
            $table->text('terms_conditions')->nullable();
            $this->legacyTimestamps($table);

            $table->index('car_id', 'idx_monthly_contracts_car_id');
            $table->index('driver_id', 'idx_monthly_contracts_driver_id');
            $table->index('operator_id', 'idx_monthly_contracts_operator_id');
            $table->index(['status', 'end_date'], 'idx_monthly_contracts_status_end_date');
            $table->index('renewal_parent_id', 'idx_monthly_contracts_renewal_parent_id');
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('car_id')->references('id')->on('monthly_cars')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('driver_id')->references('id')->on('monthly_drivers')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('operator_id')->references('id')->on('operators')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('renewal_parent_id')->references('id')->on('monthly_contracts')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Automatic rollback is disabled for legacy fleet tables. Use an approved backup/restore plan.');
    }

    private function legacyTimestamps(Blueprint $table): void
    {
        $table->timestamp('created_at')->useCurrent();
        $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
    }
};
