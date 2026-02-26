<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashOutflow extends Model
{
    protected $table = 'cash_outflow';

    protected $fillable = [
        'outflow_num', 'date', 'register_date', 'modification_date',
        'concept', 'total_amount', 'record_type', 'state',
        'type_cash_outflow_id', 'cash_opening_id', 'cash_id', 'warehouse_id', 'user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'register_date' => 'datetime',
        'modification_date' => 'datetime',
    ];

    public function cash()
    {
        return $this->belongsTo(Cash::class);
    }

    public function cashOpening()
    {
        return $this->belongsTo(CashOpening::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->belongsTo(TypeCashOutflow::class, 'type_cash_outflow_id');
    }
}
