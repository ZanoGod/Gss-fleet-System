<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\MonthlyCar;
use App\Models\MonthlyContract;
use App\Models\MonthlyDriver;
use App\Models\MonthlyContractSequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyContractService
{
    private const SNAPSHOT_FIELDS = [
        'customer_id','customer_name','guest_name','contact_person','customer_phone','customer_email','customer_address',
        'assignment_type','car_id','driver_id','operator_id','even_odd','operation_sheet','monthly_rate','rental_currency',
        'rental_status','note','prepared_by','invoice_sender','checked_by','off_day','service_hour','insurance_price',
        'insurance_company','terms_conditions',
    ];

    public function save(array $data, ?MonthlyContract $existing = null): MonthlyContract
    {
        return DB::transaction(function () use ($data, $existing): MonthlyContract {
            $record = $existing ? MonthlyContract::query()->lockForUpdate()->findOrFail($existing->id) : new MonthlyContract();
            // Coordinate customer assignment with CustomerService::delete.
            if (! empty($data['customer_id'])) {
                Customer::query()->lockForUpdate()->findOrFail($data['customer_id']);
            }
            $this->lockAndValidateResources($data, $record->id ?: null);
            if (! $record->exists) {
                $customer = Customer::query()->findOrFail($data['customer_id']);
                $this->fillCustomerSnapshotDefaults($data, $customer);
                $data['contract_no'] = $this->nextContractNumber($data['start_date']);
            } else {
                $data['contract_no'] = $record->contract_no;
            }
            unset($data['renewal_parent_id']);
            if (! empty($data['operator_id'])) {
                $data['operator_name'] = DB::table('operators')->where('id', $data['operator_id'])->value('full_name');
            }
            $record->fill($data);
            $record->save();
            $this->syncCustomerStatus($record);
            return $record->load('customer','car','driver','operator','renewalParent','renewals');
        }, 3);
    }

    public function renew(MonthlyContract $parent, array $data): MonthlyContract
    {
        return DB::transaction(function () use ($parent, $data): MonthlyContract {
            $parent = MonthlyContract::query()->lockForUpdate()->findOrFail($parent->id);
            if ($parent->renewals()->exists() || $parent->status === 'Renewed') {
                throw ValidationException::withMessages(['renewal' => 'This contract has already been renewed.']);
            }
            if ($parent->status === 'Cancelled') {
                throw ValidationException::withMessages(['renewal' => 'A cancelled contract cannot be renewed.']);
            }

            $start = Carbon::parse($parent->end_date)->addDay();
            $end = $start->copy()->addMonth()->subDay();
            $data['start_date'] = $start->toDateString();
            $data['end_date'] = $end->toDateString();
            $data['status'] = 'Active';
            $customerId = $data['customer_id'] ?? $parent->customer_id;
            if ($customerId) {
                Customer::query()->lockForUpdate()->findOrFail($customerId);
            }
            $this->lockAndValidateResources($data, $parent->id);
            $child = new MonthlyContract();
            $child->fill(array_merge($parent->only(self::SNAPSHOT_FIELDS), $data));
            $child->contract_no = $this->nextContractNumber($data['start_date']);
            $child->renewal_parent_id = $parent->id;
            $child->save();

            $parent->status = 'Renewed';
            $parent->save();
            $this->syncCustomerStatus($child);
            return $child->load('customer','car','driver','operator','renewalParent','renewals');
        }, 3);
    }

    public function delete(MonthlyContract $contract): void
    {
        DB::transaction(function () use ($contract): void {
            $contract = MonthlyContract::query()->lockForUpdate()->findOrFail($contract->id);
            if ($contract->renewalParent()->exists() || $contract->renewals()->exists()) {
                throw ValidationException::withMessages(['contract' => 'A contract with renewal history cannot be deleted.']);
            }
            $contract->delete();
        }, 3);
    }

    private function lockAndValidateResources(array $data, ?int $exceptId): void
    {
        $car = null;
        $driver = null;
        if (! empty($data['car_id'])) {
            $car = MonthlyCar::query()->lockForUpdate()->findOrFail($data['car_id']);
            if (in_array($car->availability_status, ['Maintenance','Inactive'], true)) {
                throw ValidationException::withMessages(['car_id' => 'Monthly vehicle is under maintenance or inactive.']);
            }
        }
        if (! empty($data['driver_id'])) {
            $driver = MonthlyDriver::query()->lockForUpdate()->findOrFail($data['driver_id']);
            if (in_array($driver->driver_status, ['Leave','Inactive'], true)) {
                throw ValidationException::withMessages(['driver_id' => 'Monthly driver is on leave or inactive.']);
            }
        }
        if (! in_array($data['status'] ?? null, ['Draft','Active'], true)) {
            return;
        }
        if (! $car && ! $driver) {
            return;
        }
        $conflict = MonthlyContract::query()->whereIn('status', ['Draft','Active'])
            ->where('start_date', '<=', $data['end_date'])->where('end_date', '>=', $data['start_date'])
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where(function ($query) use ($data): void {
                if (! empty($data['car_id'])) $query->where('car_id', $data['car_id']);
                if (! empty($data['driver_id'])) {
                    $method = ! empty($data['car_id']) ? 'orWhere' : 'where';
                    $query->{$method}('driver_id', $data['driver_id']);
                }
            })->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['start_date' => 'The monthly vehicle or driver is already assigned during these contract dates.']);
        }
    }

    private function fillCustomerSnapshotDefaults(array &$data, Customer $customer): void
    {
        $defaults = [
            'customer_name' => $customer->company_name ?: $customer->guest_name,
            'guest_name' => $customer->guest_name,
            'contact_person' => $customer->contact_person,
            'customer_phone' => $customer->contact_phone,
            'customer_email' => $customer->contact_email,
            'customer_address' => $customer->customer_address,
            'off_day' => $customer->off_day,
            'service_hour' => $customer->service_hour,
        ];
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') $data[$key] = $value;
        }
    }

    private function nextContractNumber(string $startDate): string
    {
        $month = Carbon::parse($startDate)->format('Ym');
        MonthlyContractSequence::query()->insertOrIgnore(['month_key' => $month, 'next_value' => 1]);
        $sequence = MonthlyContractSequence::query()->whereKey($month)->lockForUpdate()->firstOrFail();
        $prefix = "MC-{$month}-";
        $last = MonthlyContract::query()->where('contract_no', 'like', $prefix.'%')->orderByDesc('contract_no')->value('contract_no');
        $lastNumber = $last && preg_match('/^MC-\d{6}-(\d+)$/', $last, $matches) ? (int) $matches[1] : 0;
        $next = max($sequence->next_value, $lastNumber + 1);
        $sequence->next_value = $next + 1;
        $sequence->save();
        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function syncCustomerStatus(MonthlyContract $contract): void
    {
        if ($contract->status === 'Active' && $contract->customer_id) {
            Customer::query()->whereKey($contract->customer_id)->update(['customer_status' => 'Active']);
        }
    }
}
