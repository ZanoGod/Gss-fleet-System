<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\DailyBooking;
use App\Models\MonthlyCar;
use App\Models\MonthlyContract;
use App\Models\User;
use App\Models\Admin;
use App\Models\AdminUserLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyFleetSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_legacy_domain_tables_without_replacing_laravel_users(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        foreach ([
            'admins', 'bookings', 'cars', 'customers', 'drivers',
            'monthly_cars', 'monthly_contracts', 'monthly_drivers', 'operators', 'admin_user_links',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing legacy table {$table}.");
        }

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasColumn('users', 'username'));
        $this->assertFalse(Schema::hasColumn('admins', 'name'));
        $this->assertFalse(Schema::hasColumn('admins', 'password'));
    }

    public function test_daily_and_monthly_resource_relations_remain_separate_even_when_ids_match(): void
    {
        $dailyCar = Car::create([
            'car_type' => 'Sedan', 'plate_no' => '1A-0001', 'model_name' => 'Daily',
            'seat_capacity' => 4, 'availability_status' => 'Available',
        ]);
        $monthlyCar = MonthlyCar::create([
            'car_type' => 'Sedan', 'plate_no' => '1A-0001', 'model_name' => 'Monthly',
            'seat_capacity' => 4, 'availability_status' => 'Available',
        ]);

        $this->assertSame($dailyCar->id, $monthlyCar->id);

        $booking = DailyBooking::create([
            'guest_company_name' => 'Example Co', 'car_id' => $dailyCar->id,
            'operator_name' => 'Snapshot Operator', 'even_odd' => 'Even',
            'start_date' => '2026-11-01', 'end_date' => '2026-11-02', 'status' => 'Pending',
        ]);
        $contract = MonthlyContract::create([
            'contract_no' => 'MC-202611-0001', 'customer_name' => 'Example Co',
            'assignment_type' => 'Vehicle Only', 'car_id' => $monthlyCar->id,
            'start_date' => '2026-11-01', 'end_date' => '2026-11-30',
            'monthly_rate' => '100.00', 'rental_currency' => 'MMK',
            'rental_status' => 'Including fuel', 'status' => 'Active',
            'insurance_price' => '0.00',
        ]);

        $this->assertInstanceOf(Car::class, $booking->fresh()->car);
        $this->assertInstanceOf(MonthlyCar::class, $contract->fresh()->car);
        $this->assertSame('100.00', $contract->fresh()->monthly_rate);
        $this->assertSame('2026-11-01', $booking->fresh()->start_date->format('Y-m-d'));
    }

    public function test_contract_vehicle_foreign_key_targets_monthly_cars_and_amounts_are_decimal(): void
    {
        $foreignKeys = Schema::getForeignKeys('monthly_contracts');
        $carForeignKey = collect($foreignKeys)->firstWhere('columns', ['car_id']);

        $this->assertNotNull($carForeignKey);
        $this->assertSame('monthly_cars', $carForeignKey['foreign_table']);
        // SQLite reports DECIMAL-affinity columns as NUMERIC; the migration retains DECIMAL(12,2).
        $this->assertContains(Schema::getColumnType('monthly_contracts', 'monthly_rate'), ['decimal', 'numeric']);
        $this->assertContains(Schema::getColumnType('monthly_contracts', 'insurance_price'), ['decimal', 'numeric']);
        $this->assertSame('decimal:2', (new MonthlyContract)->getCasts()['monthly_rate']);
        $this->assertSame('date', Schema::getColumnType('bookings', 'start_date'));
    }

    public function test_fleet_schema_does_not_automatically_link_admins_to_laravel_users(): void
    {
        $user = User::factory()->create(['username' => 'local-user']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'local-user']);
        $this->assertDatabaseCount('admins', 0);
        $this->assertDatabaseCount('admin_user_links', 0);
        $this->assertSame([], $user->getRelations());
    }

    public function test_legacy_admin_identity_requires_an_explicit_link_and_active_admin(): void
    {
        $user = User::factory()->create();
        $admin = Admin::create([
            'username' => 'fleet-admin', 'email' => null,
            'password_hash' => 'hash-is-not-returned', 'is_active' => true,
        ]);
        $resolver = app(\App\Services\LegacyAdminIdentity::class);

        try {
            $resolver->requireActiveAdminId($user);
            $this->fail('An unlinked Laravel user must not resolve to a legacy admin.');
        } catch (AuthorizationException) {
            $this->assertDatabaseCount('admin_user_links', 0);
        }

        AdminUserLink::create(['user_id' => $user->id, 'admin_id' => $admin->id]);
        $this->assertSame($admin->id, $resolver->requireActiveAdminId($user));
        $this->assertArrayNotHasKey('password_hash', $admin->toArray());

        $admin->update(['is_active' => false]);
        $this->expectException(AuthorizationException::class);
        $resolver->requireActiveAdminId($user->fresh());
    }
}
