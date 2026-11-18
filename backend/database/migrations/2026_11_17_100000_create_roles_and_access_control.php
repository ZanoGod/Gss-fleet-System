<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ADMIN_PERMISSIONS = [
        'dashboard.view',
        'bookings.view', 'bookings.manage', 'contracts.view', 'contracts.manage',
        'fleet.view', 'fleet.manage', 'customers.view', 'customers.manage',
        'drivers.view', 'drivers.manage', 'operators.view', 'operators.manage',
        'maintenance.view', 'maintenance.manage', 'car_licenses.view', 'car_licenses.manage',
        'insurance.view', 'insurance.manage', 'finance.view', 'finance.manage',
        'reports.view', 'reports.manage',
        'users.view', 'users.create', 'users.update', 'users.delete',
        'access.manage', 'roles.view', 'roles.manage',
    ];

    private const STAFF_PERMISSIONS = [
        'dashboard.view', 'bookings.view', 'contracts.view', 'fleet.view', 'customers.view',
        'drivers.view', 'operators.view', 'maintenance.view', 'car_licenses.view',
        'insurance.view', 'finance.view', 'reports.view',
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->json('permissions');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->after('username')->constrained('roles')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('role_id');
        });

        $now = now();
        DB::table('roles')->insert([
            ['name' => 'Administrator', 'slug' => 'administrator', 'permissions' => json_encode(self::ADMIN_PERMISSIONS), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Staff', 'slug' => 'staff', 'permissions' => json_encode(self::STAFF_PERMISSIONS), 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        // Preserve the access current accounts already had before introducing roles.
        DB::table('users')->whereNull('role_id')->update(['role_id' => DB::table('roles')->where('slug', 'administrator')->value('id')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn('is_active');
        });
        Schema::dropIfExists('roles');
    }
};
