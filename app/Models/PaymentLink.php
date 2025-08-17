<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLink extends Model
{
    protected $fillable = [
        'order_id',
        'external_url',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
