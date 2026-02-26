<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashUser extends Model
{
    protected $table = 'cash_user';

    protected $fillable = ['user_id', 'cash_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cash()
    {
        return $this->belongsTo(Cash::class);
    }

    public function getCashNameAttribute()
    {
        return $this->cash ? $this->cash->name : '';
    }
}
