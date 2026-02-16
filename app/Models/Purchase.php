<?php

namespace App\Models;

use App\Models\Concerns\HasTaxNetAttribute;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasTaxNetAttribute;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'date', 'Ref', 'provider_id', 'warehouse_id', 'GrandTotal', 'time',
        'discount', 'shipping', 'statut', 'notes', 'tax_net', 'tax_rate', 'paid_amount',
        'payment_statut', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'provider_id' => 'integer',
        'warehouse_id' => 'integer',
        'GrandTotal' => 'double',
        'discount' => 'double',
        'shipping' => 'double',
        'tax_net' => 'double',
        'tax_rate' => 'double',
        'paid_amount' => 'double',
    ];

    public function details()
    {
        return $this->hasMany('App\Models\PurchaseDetail');
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider');
    }

    public function facture()
    {
        return $this->hasMany('App\Models\PaymentPurchase');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Models\Warehouse');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function documents()
    {
        return $this->hasMany('App\Models\PurchaseDocument', 'purchase_id');
    }
}
