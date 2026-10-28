<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MonthlyCar;
use App\Models\MonthlyContract;
use App\Models\MonthlyDriver;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyContractApiTest extends TestCase
{
    use RefreshDatabase;
    private User $user;
    private Customer $customer;
    private MonthlyCar $car;
    private MonthlyDriver $driver;
    private Operator $operator;

    protected function setUp():void
    {
        parent::setUp();$this->user=User::factory()->create();
        $this->customer=Customer::create(['company_name'=>'Contract Guest Co','guest_name'=>'Guest','contact_person'=>'Contact','contact_phone'=>'09-123','contact_email'=>'guest@example.test','customer_address'=>'Yangon','customer_status'=>'Inactive','off_day'=>'Sunday','service_hour'=>'08:00-17:00']);
        $this->car=MonthlyCar::create(['car_type'=>'Van','plate_no'=>'MONTH-1','model_name'=>'Monthly Van','seat_capacity'=>8,'availability_status'=>'Available']);
        $this->driver=MonthlyDriver::create(['full_name'=>'Monthly Driver','phone_number'=>'09-555','driver_status'=>'Available']);
        $this->operator=Operator::create(['full_name'=>'Contract Operator','operator_status'=>'Active']);
    }

    private function data(array $overrides=[]):array
    {
        return array_merge(['customer_id'=>$this->customer->id,'customer_name'=>'Contract Guest Co','guest_name'=>'Guest','contact_person'=>'Contact','customer_phone'=>'09-123','customer_email'=>'guest@example.test','customer_address'=>'Yangon','assignment_type'=>'Vehicle and Driver','car_id'=>$this->car->id,'driver_id'=>$this->driver->id,'operator_id'=>$this->operator->id,'even_odd'=>'Even','operation_sheet'=>'','start_date'=>'2026-11-01','end_date'=>'2026-11-30','monthly_rate'=>'125000','rental_currency'=>'MMK','rental_status'=>'Including fuel','status'=>'Active','note'=>'Note','prepared_by'=>'Prepared','invoice_sender'=>'Accounts','checked_by'=>'Checked','off_day'=>'Sunday','service_hour'=>'08:00-17:00','insurance_price'=>'15000','insurance_company'=>'Contract snapshot insurer','terms_conditions'=>'Terms'],$overrides);
    }

    public function test_create_preserves_monthly_resources_snapshots_and_exact_legacy_number():void
    {
        $response=$this->actingAs($this->user)->postJson('/api/monthly-contracts',$this->data());
        $response->assertCreated()->assertJsonPath('data.contract_no','MC-202611-0001')->assertJsonPath('data.car.plate_no','MONTH-1')->assertJsonPath('data.operator_name','Contract Operator');
        $this->assertDatabaseHas('monthly_contracts',['id'=>$response->json('data.id'),'car_id'=>$this->car->id,'driver_id'=>$this->driver->id,'insurance_company'=>'Contract snapshot insurer','insurance_price'=>'15000.00']);
        $this->assertSame('Active',$this->customer->fresh()->customer_status);
    }

    public function test_contract_renewal_uses_legacy_dates_number_and_lineage_once():void
    {
        $parent=$this->actingAs($this->user)->postJson('/api/monthly-contracts',$this->data())->assertCreated()->json('data');
        $renewal=$this->actingAs($this->user)->postJson("/api/monthly-contracts/{$parent['id']}/renew",$this->data(['contract_no'=>'CLIENT-NUMBER','start_date'=>'2027-01-01','end_date'=>'2027-01-31']))->assertCreated();
        $renewal->assertJsonPath('data.contract_no','MC-202612-0001')->assertJsonPath('data.start_date','2026-12-01')->assertJsonPath('data.end_date','2026-12-31')->assertJsonPath('data.renewal_parent_id',$parent['id']);
        $this->assertSame('Renewed',MonthlyContract::findOrFail($parent['id'])->status);
        $this->actingAs($this->user)->postJson("/api/monthly-contracts/{$parent['id']}/renew",$this->data())->assertUnprocessable()->assertJsonValidationErrors('renewal');
    }

    public function test_monthly_resource_overlap_is_blocked_and_vehicle_only_can_omit_driver():void
    {
        $this->actingAs($this->user)->postJson('/api/monthly-contracts',$this->data())->assertCreated();
        $this->actingAs($this->user)->postJson('/api/monthly-contracts',$this->data(['contract_no'=>'IGNORED','assignment_type'=>'Vehicle Only','driver_id'=>null,'start_date'=>'2026-11-30','end_date'=>'2026-12-15']))->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $this->actingAs($this->user)->postJson('/api/monthly-contracts',$this->data(['assignment_type'=>'Driver Only','car_id'=>null,'start_date'=>'2026-11-30','end_date'=>'2026-12-15']))->assertUnprocessable()->assertJsonValidationErrors('start_date');
        $vehicleOnly=$this->data(['assignment_type'=>'Vehicle Only','driver_id'=>null,'car_id'=>null]);
        $this->actingAs($this->user)->postJson('/api/monthly-contracts',$vehicleOnly)->assertUnprocessable()->assertJsonValidationErrors('car_id');
    }

    public function test_invalid_enum_and_non_whole_price_are_rejected():void
    {
        $this->actingAs($this->user)->postJson('/api/monthly-contracts',$this->data(['assignment_type'=>'Both','monthly_rate'=>'100.50']))->assertUnprocessable()->assertJsonValidationErrors(['assignment_type','monthly_rate']);
    }
}
