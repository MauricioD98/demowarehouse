<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeCashOutflow extends Model
{
    protected $table = 'type_cash_outflow';

    protected $fillable = ['name', 'description', 'state'];

    public function outflows()
    {
        return $this->hasMany(CashOutflow::class, 'type_cash_outflow_id');
    }
}
