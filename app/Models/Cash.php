<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cash extends Model
{
    protected $table = 'cash';

    protected $fillable = [
        'code', 'name', 'description', 'state', 'warehouse_id',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inflows()
    {
        return $this->hasMany(CashInflow::class);
    }

    public function outflows()
    {
        return $this->hasMany(CashOutflow::class);
    }

    public function openings()
    {
        return $this->hasMany(CashOpening::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'cash_user', 'cash_id', 'user_id');
    }

    public function cashOpening()
    {
        return $this->hasMany(CashOpening::class)->whereNull('closing_date');
    }

    public function getBalanceAttribute()
    {
        $cashOpening = $this->cashOpening()->first();
        if (! $cashOpening) {
            return 0;
        }
        $openingAmount = (float) $cashOpening->opening_amount;
        $totalInflow = $this->inflows()->where('cash_opening_id', $cashOpening->id)->where('state', 1)->sum('total_amount');
        $totalOutflow = $this->outflows()->where('cash_opening_id', $cashOpening->id)->where('state', 1)->sum('total_amount');

        return ($openingAmount + $totalInflow) - $totalOutflow;
    }
}
