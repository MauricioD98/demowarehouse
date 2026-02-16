<?php

namespace App\Models;

use App\Models\Concerns\HasTaxNetAttribute;
use Illuminate\Database\Eloquent\Model;

class TransferDetail extends Model
{
    use HasTaxNetAttribute;

    protected $table = 'transfer_details';

    protected $guarded = ['id'];

    protected $fillable = [
        'transfer_id', 'quantity', 'purchase_unit_id', 'product_id', 'total', 'product_variant_id',
        'cost', 'tax_net', 'discount', 'discount_method', 'tax_method',
    ];

    protected $casts = [
        'total' => 'double',
        'cost' => 'double',
        'tax_net' => 'double',
        'discount' => 'double',
        'quantity' => 'double',
        'transfer_id' => 'integer',
        'purchase_unit_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
    ];

    public function transfer()
    {
        return $this->belongsTo('App\Models\Transfer');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }
}
