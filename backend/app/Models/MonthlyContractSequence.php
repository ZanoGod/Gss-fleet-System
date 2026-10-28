<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyContractSequence extends Model
{
    protected $table = 'monthly_contract_sequences';
    protected $primaryKey = 'month_key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['month_key', 'next_value'];
    protected function casts(): array { return ['next_value' => 'integer']; }
}
