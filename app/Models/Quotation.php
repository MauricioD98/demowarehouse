<?php

namespace App\Models;

use App\Models\Concerns\HasTaxNetAttribute;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    use HasTaxNetAttribute;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'date', 'Ref', 'client_id', 'GrandTotal', 'warehouse_id', 'user_id', 'statut', 'time',
        'notes', 'discount', 'shipping', 'tax_net', 'tax_rate', 'created_at', 'updated_at', 'deleted_at',
    ];

    protected $casts = [
        'GrandTotal' => 'double',
        'user_id' => 'integer',
        'client_id' => 'integer',
        'warehouse_id' => 'integer',
        'discount' => 'double',
        'shipping' => 'double',
        'tax_net' => 'double',
        'tax_rate' => 'double',

    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function details()
    {
        return $this->hasMany('App\Models\QuotationDetail');
    }

    public function client()
    {
        return $this->belongsTo('App\Models\Client');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Models\Warehouse');
    }
}
