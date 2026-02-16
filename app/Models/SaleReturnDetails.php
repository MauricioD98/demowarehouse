<?php

namespace App\Models;

use App\Models\Concerns\HasTaxNetAttribute;
use Illuminate\Database\Eloquent\Model;

class SaleReturnDetails extends Model
{
    use HasTaxNetAttribute;

    protected $guarded = ['id'];

    protected $fillable = [
        'product_id', 'sale_return_id', 'sale_unit_id', 'total', 'quantity', 'product_variant_id',
        'price', 'tax_net', 'discount', 'discount_method', 'tax_method',
    ];

    protected $casts = [
        'total' => 'double',
        'quantity' => 'double',
        'sale_return_id' => 'integer',
        'product_id' => 'integer',
        'sale_unit_id' => 'integer',
        'product_variant_id' => 'integer',
        'price' => 'double',
        'tax_net' => 'double',
        'discount' => 'double',
    ];

    public function SaleReturn()
    {
        return $this->belongsTo('App\Models\SaleReturn');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }
}
