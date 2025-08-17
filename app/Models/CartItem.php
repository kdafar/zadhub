<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'external_item_id', // item id from provider
        'title',
        'qty',
        'price',
        'addons',           // array of {id,title,price}
        'image',
        'meta',
    ];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'decimal:3',
        'addons' => 'array',
        'meta' => 'array',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
}
