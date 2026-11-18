<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_entries', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['income', 'expense']);
            $table->string('category', 80);
            $table->string('description', 180);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('MMK');
            $table->date('entry_date');
            $table->enum('status', ['posted', 'draft'])->default('posted');
            $table->string('payment_method', 40)->nullable();
            $table->string('reference', 100)->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('daily_booking_id')->nullable();
            $table->unsignedInteger('monthly_contract_id')->nullable();
            $table->enum('vehicle_fleet', ['daily', 'monthly'])->nullable();
            $table->unsignedInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('maintenance_id')->nullable();
            $table->unsignedBigInteger('insurance_policy_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('daily_booking_id')->references('id')->on('bookings')->nullOnDelete();
            $table->foreign('monthly_contract_id')->references('id')->on('monthly_contracts')->nullOnDelete();
            $table->foreign('maintenance_id')->references('id')->on('vehicle_maintenances')->nullOnDelete();
            $table->foreign('insurance_policy_id')->references('id')->on('insurance_policies')->nullOnDelete();
            $table->index(['type', 'entry_date', 'currency']);
            $table->index(['vehicle_fleet', 'vehicle_id', 'entry_date']);
        });

        Schema::create('finance_invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->unsignedBigInteger('income_entry_id')->nullable()->unique();
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('daily_booking_id')->nullable();
            $table->unsignedInteger('monthly_contract_id')->nullable();
            $table->string('description', 180);
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->string('currency', 3)->default('MMK');
            $table->enum('status', ['draft', 'sent', 'void'])->default('sent');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign('income_entry_id')->references('id')->on('finance_entries')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('daily_booking_id')->references('id')->on('bookings')->nullOnDelete();
            $table->foreign('monthly_contract_id')->references('id')->on('monthly_contracts')->nullOnDelete();
            $table->index(['status', 'due_date']);
        });

        Schema::create('finance_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('finance_invoices')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('MMK');
            $table->string('payment_method', 40);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['payment_date', 'currency']);
        });

        // Migrate existing role grants in place so staff keep the access they already had.
        if (Schema::hasTable('roles')) {
            foreach (DB::table('roles')->get(['id', 'permissions']) as $role) {
                $permissions = json_decode((string) $role->permissions, true) ?: [];
                $permissions = array_values(array_unique(array_map(
                    fn (string $permission): string => match ($permission) {
                        'payments.view' => 'finance.view',
                        'payments.manage' => 'finance.manage',
                        default => $permission,
                    },
                    $permissions,
                )));
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payments');
        Schema::dropIfExists('finance_invoices');
        Schema::dropIfExists('finance_entries');
        // Intentionally retain renamed permission grants on rollback to avoid revoking access.
    }
};
