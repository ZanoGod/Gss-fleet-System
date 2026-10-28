<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminUserLink;
use App\Models\Car;
use App\Models\InsurancePolicy;
use App\Models\InsuranceDocument;
use App\Models\InsuranceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InsuranceApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Car $car;
    private InsuranceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $admin = Admin::create(['username' => 'linked-admin', 'password_hash' => 'not-copied', 'is_active' => true]);
        AdminUserLink::create(['user_id' => $this->user->id, 'admin_id' => $admin->id]);
        $this->car = Car::create(['car_type' => 'Sedan', 'plate_no' => 'INS-001', 'model_name' => 'Policy test car', 'seat_capacity' => 4, 'availability_status' => 'Available']);
        $this->provider = InsuranceProvider::create(['name' => 'GSS Insurance']);
    }

    private function policyData(array $overrides = []): array
    {
        return array_merge([
            'car_id' => $this->car->id,
            'provider_id' => $this->provider->id,
            'policy_number' => 'POL-1001',
            'insurance_type' => '  Comprehensive ',
            'start_date' => '2026-11-01',
            'expiration_date' => '2027-10-31',
            'premium_amount' => '125000.00',
            'coverage_amount' => '10000000.00',
            'currency' => 'MMK',
        ], $overrides);
    }

    public function test_dashboard_and_providers_allow_a_signed_in_user_without_admin_links(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/insurance/dashboard')->assertOk()->assertJsonPath('data.total_policies', 0);
        $this->actingAs($user)->getJson('/api/insurance/providers?per_page=100')->assertOk();
    }

    public function test_insurance_routes_allow_authenticated_users_without_legacy_links(): void
    {
        $unlinked = User::factory()->create();
        $this->actingAs($unlinked)->getJson('/api/insurance/policies')->assertOk();
        $this->actingAs($this->user)->getJson('/api/insurance/policies')->assertOk();
    }

    public function test_policy_creation_normalizes_insurance_type_and_protects_legacy_admin_identity(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/insurance/policies', $this->policyData());
        $response->assertCreated()->assertJsonPath('data.insurance_type', 'comprehensive')
            ->assertJsonPath('data.vehicle.fleet', 'daily');
        $this->assertDatabaseHas('insurance_policies', [
            'id' => $response->json('data.id'), 'created_by_user_id' => $this->user->id,
            'car_id' => $this->car->id, 'monthly_car_id' => null,
        ]);
    }

    public function test_policy_overlap_is_blocked_and_renewal_preserves_lineage(): void
    {
        $this->actingAs($this->user)->postJson('/api/insurance/policies', $this->policyData())->assertCreated();
        $this->actingAs($this->user)->postJson('/api/insurance/policies', $this->policyData(['policy_number' => 'POL-1002']))
            ->assertUnprocessable()->assertJsonValidationErrors('start_date');

        $parent = InsurancePolicy::query()->firstOrFail();
        $renewal = $this->actingAs($this->user)->postJson("/api/insurance/policies/{$parent->id}/renew", $this->policyData([
            'policy_number' => 'POL-1001-R1', 'start_date' => '2027-11-01', 'expiration_date' => '2028-10-31',
        ]));
        $renewal->assertCreated()->assertJsonPath('data.renewal_parent_id', $parent->id);
        $this->assertSame($renewal->json('data.id'), $parent->fresh()->renewal->id);
        $this->actingAs($this->user)->postJson("/api/insurance/policies/{$parent->id}/renew", $this->policyData([
            'policy_number' => 'POL-1001-R2', 'start_date' => '2027-11-01', 'expiration_date' => '2028-10-31',
        ]))->assertUnprocessable();
    }

    public function test_claim_event_is_appended_and_document_is_stored_privately(): void
    {
        Storage::fake('insurance');
        $policy = new InsurancePolicy($this->policyData(['insurance_type' => 'comprehensive', 'start_date' => '2025-11-01']));
        $policy->forceFill(['created_by' => $this->user->legacyAdminLink->admin_id])->save();
        $claim = $this->actingAs($this->user)->postJson('/api/insurance/claims', [
            'policy_id' => $policy->id, 'incident_date' => '2026-10-01', 'reference_number' => 'CLM-1',
            'amount' => '1000.00', 'status' => 'submitted',
        ]);
        $claim->assertCreated()->assertJsonCount(1, 'data.events');
        $claimId = $claim->json('data.id');
        $this->actingAs($this->user)->putJson("/api/insurance/claims/{$claimId}", [
            'policy_id' => $policy->id, 'incident_date' => '2026-10-01', 'reference_number' => 'CLM-1',
            'amount' => '1000.00', 'status' => 'under_review',
        ])->assertOk()->assertJsonCount(2, 'data.events');

        $upload = $this->actingAs($this->user)->post("/api/insurance/policies/{$policy->id}/documents", [
            'document' => UploadedFile::fake()->create('policy.pdf', 30, 'application/pdf'),
        ]);
        $upload->assertCreated()->assertJsonMissingPath('data.storage_key');
        $docId = $upload->json('data.id');
        $this->actingAs($this->user)->get("/api/insurance/documents/{$docId}/download")->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $replacement = $this->actingAs($this->user)->post("/api/insurance/policies/{$policy->id}/documents", [
            'document' => UploadedFile::fake()->create('policy-revised.pdf', 35, 'application/pdf'),
            'replaces_id' => $docId,
        ]);
        $replacement->assertCreated()->assertJsonPath('data.replaces_id', $docId);
        $this->assertNotNull(InsuranceDocument::findOrFail($docId)->archived_at);
        $unlinked = User::factory()->create();
        $this->actingAs($unlinked)->get("/api/insurance/documents/{$docId}/download")->assertOk();
    }
}
