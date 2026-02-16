<?php

namespace App\Models;

use App\Models\Concerns\HasTaxNetAttribute;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnDetails extends Model
{
    use HasTaxNetAttribute;

    protected $guarded = ['id'];

    protected $fillable = [
        'product_id', 'purchase_return_id', 'purchase_unit_id', 'total', 'quantity', 'product_variant_id',
        'cost', 'tax_net', 'discount', 'discount_method', 'tax_method',
    ];

    protected $casts = [
        'total' => 'double',
        'quantity' => 'double',
        'purchase_return_id' => 'integer',
        'purchase_unit_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'cost' => 'double',
        'tax_net' => 'double',
        'discount' => 'double',
    ];

    public function PurchaseReturn()
    {
        return $this->belongsTo('App\Models\PurchaseReturn');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }
}
