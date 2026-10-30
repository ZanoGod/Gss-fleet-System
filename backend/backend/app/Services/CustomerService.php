<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function save(array $data, ?Customer $customer = null): Customer
    {
        return DB::transaction(function () use ($data, $customer): Customer {
            $record = $customer
                ? Customer::query()->lockForUpdate()->findOrFail($customer->id)
                : new Customer();
            unset($data['customer_status']);
            $record->fill($data);
            if (! $record->exists) {
                $record->customer_status = 'Inactive';
            }
            $record->save();
            return $record;
        }, 3);
    }

    /** Returns false when any contract still refers to this customer. */
    public function delete(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer): bool {
            $record = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            if ($record->monthlyContracts()->exists()) {
                return false;
            }
            $record->delete();
            return true;
        }, 3);
    }
}
