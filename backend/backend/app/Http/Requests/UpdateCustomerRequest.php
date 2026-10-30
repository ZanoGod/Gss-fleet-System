<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;

class UpdateCustomerRequest extends StoreCustomerRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('customer'));
    }
}
