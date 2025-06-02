<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'price',
        'quantity',
        'product_id',
        'order_id',
        'discount_percentage',
        'price_after_discount',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getPriceAfterDiscountAttribute()
    {
        return $this->price - ($this->price * ($this->discount_percentage / 100));
    }
}
