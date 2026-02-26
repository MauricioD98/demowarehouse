<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashOpening extends Model
{
    protected $table = 'cash_opening';

    protected $fillable = [
        'cash_id', 'warehouse_id', 'cash_close_number', 'total_sale', 'opening_amount',
        'total_outflow', 'total_inflow', 'closing_amount', 'total_cash',
        'opening_date', 'closing_date', 'register_date', 'modification_date',
        'cash_state', 'state', 'opening_user_id', 'closing_user_id',
    ];

    protected $casts = [
        'opening_date' => 'datetime',
        'closing_date' => 'datetime',
        'register_date' => 'datetime',
    ];

    public function cash()
    {
        return $this->belongsTo(Cash::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function openingUser()
    {
        return $this->belongsTo(User::class, 'opening_user_id');
    }

    public function closingUser()
    {
        return $this->belongsTo(User::class, 'closing_user_id');
    }

    public function inflows()
    {
        return $this->hasMany(CashInflow::class);
    }

    public function outflows()
    {
        return $this->hasMany(CashOutflow::class);
    }
}
