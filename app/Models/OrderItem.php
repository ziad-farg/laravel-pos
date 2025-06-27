<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'price',
        'quantity',
        'product_id',
        'order_id',
        'discount_type',
        'discount_value',
        'quantity_returned',
    ];

    protected $casts = [
        'discount_type' => DiscountType::class,
        'price' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'quantity' => 'integer',
        'quantity_returned' => 'integer',
    ];

    protected $appends = [
        'total_price_for_item',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Getters for calculated total price for the item
    public function getTotalPriceForItemAttribute()
    {
        return number_format($this->price * $this->quantity, 2, '.', '');
    }
}
