<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashInflow extends Model
{
    protected $table = 'cash_inflow';

    protected $fillable = [
        'payment_sale_id', 'inflow_num', 'date', 'register_date', 'modification_date',
        'concept', 'total_amount', 'record_type', 'reglement', 'state',
        'type_cash_inflow_id', 'cash_opening_id', 'cash_id', 'warehouse_id', 'user_id',
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
        return $this->belongsTo(TypeCashInflow::class, 'type_cash_inflow_id');
    }

    public function paymentSale()
    {
        return $this->belongsTo(PaymentSale::class);
    }
}
