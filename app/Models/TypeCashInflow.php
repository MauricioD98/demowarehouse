<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeCashInflow extends Model
{
    protected $table = 'type_cash_inflow';

    protected $fillable = ['name', 'description', 'state'];

    public function inflows()
    {
        return $this->hasMany(CashInflow::class, 'type_cash_inflow_id');
    }
}
