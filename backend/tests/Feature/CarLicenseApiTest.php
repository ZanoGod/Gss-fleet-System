<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\MonthlyCar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarLicenseApiTest extends TestCase
{
    use RefreshDatabase;

    private Car $car;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-16 12:00:00');
        $this->user = User::factory()->create();
        $this->car = Car::create(['car_type' => 'Sedan', 'plate_no' => 'LIC-1', 'model_name' => 'Test Car', 'seat_capacity' => 5, 'availability_status' => 'Available']);
    }

    private function licenseData(array $overrides = []): array
    {
        return array_merge([
            'car_id' => $this->car->id, 'monthly_car_id' => null, 'license_number' => 'YGN-12345',
            'license_period' => 1, 'issue_date' => '2026-11-16', 'expiry_date' => '2027-11-16',
            'renewal_date' => null, 'renewal_fee' => '50000.00', 'office_region' => 'Yangon',
            'office_branch' => 'East Yangon District Office', 'remarks' => 'Registration card filed',
        ], $overrides);
    }

    public function test_authentication_validation_crud_and_expiry_summary(): void
    {
        $this->getJson('/api/car-licenses')->assertUnauthorized();
        $this->actingAs($this->user);
        $this->postJson('/api/car-licenses', $this->licenseData(['license_period' => 4]))->assertUnprocessable()->assertJsonValidationErrors('license_period');
        $this->postJson('/api/car-licenses', $this->licenseData(['expiry_date' => '2026-11-15']))->assertUnprocessable()->assertJsonValidationErrors('expiry_date');
        $id = $this->postJson('/api/car-licenses', $this->licenseData(['expiry_date' => '2026-12-10']))
            ->assertCreated()->assertJsonPath('data.status', 'Expiring Soon')->assertJsonPath('data.days_until_expiry', 24)->json('data.id');
        $this->getJson('/api/car-licenses?search=LIC-1&status=Expiring%20Soon')->assertOk()->assertJsonPath('meta.total', 1);
        $this->getJson('/api/car-licenses/summary')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.expiring_soon', 1);
        $this->putJson('/api/car-licenses/'.$id, $this->licenseData(['expiry_date' => '2027-11-16']))->assertOk()->assertJsonPath('data.status', 'Active');
        $this->getJson('/api/car-licenses/'.$id)->assertOk()->assertJsonPath('data.vehicle.plate_no', 'LIC-1');
    }

    public function test_renewal_history_and_private_document_upload(): void
    {
        Storage::fake('car-licenses');
        $this->actingAs($this->user);
        $id = $this->postJson('/api/car-licenses', $this->licenseData())->assertCreated()->json('data.id');
        $this->post('/api/car-licenses/'.$id.'/documents', ['document' => UploadedFile::fake()->create('registration.pdf', 20, 'application/pdf')])
            ->assertCreated()->assertJsonPath('data.original_name', 'registration.pdf');
        $renewalId = $this->postJson('/api/car-licenses/'.$id.'/renew', $this->licenseData([
            'car_id' => null, 'monthly_car_id' => null, 'issue_date' => '2026-11-16', 'expiry_date' => '2027-11-16',
            'renewal_date' => '2026-11-16', 'renewal_fee' => '60000.00',
        ]))->assertCreated()->assertJsonPath('data.renewal_parent_id', $id)->json('data.id');
        $this->getJson('/api/car-licenses/'.$id)->assertOk()->assertJsonPath('data.renewal_history.1.id', $renewalId);
        $this->deleteJson('/api/car-licenses/'.$id)->assertUnprocessable();
        $this->deleteJson('/api/car-licenses/'.$renewalId)->assertUnprocessable()->assertJsonValidationErrors('license');
    }

    public function test_office_region_and_branch_must_match(): void
    {
        $this->actingAs($this->user);
        $this->postJson('/api/car-licenses', $this->licenseData(['office_region' => 'Yangon', 'office_branch' => 'Mandalay Region Office']))
            ->assertUnprocessable()->assertJsonValidationErrors('office_branch');
    }

    public function test_monthly_fleet_license_links_to_its_existing_vehicle_without_copying_vehicle_data(): void
    {
        $monthlyCar = MonthlyCar::create(['car_type' => 'SUV', 'plate_no' => 'MONTH-LIC-1', 'model_name' => 'Existing Monthly Vehicle', 'seat_capacity' => 7, 'availability_status' => 'Available']);
        $this->actingAs($this->user);
        $response = $this->postJson('/api/car-licenses', $this->licenseData([
            'car_id' => null, 'monthly_car_id' => $monthlyCar->id, 'license_number' => 'MONTHLY-RTAD-1',
        ]))->assertCreated()->assertJsonPath('data.vehicle.fleet', 'monthly')->assertJsonPath('data.vehicle.plate_no', 'MONTH-LIC-1');
        $this->assertDatabaseHas('car_licenses', ['id' => $response->json('data.id'), 'car_id' => null, 'monthly_car_id' => $monthlyCar->id]);
    }
}
