<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;

class UserCartItem extends Model
{
    protected $fillable = [
        'user_cart_id',
        'product_id',
        'quantity',
        'price_at_add',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_at_add' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_type' => DiscountType::class,
    ];

    protected $appends = [
        'price_after_discount',
        'total_price_for_item',
    ];


    public function userCart()
    {
        return $this->belongsTo(UserCart::class);
    }


    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    // Calculates and returns the item's price after applying the specified discount, ensuring it is not negative.
    public function getPriceAfterDiscountAttribute()
    {
        $finalPrice = $this->price_at_add;

        if ($this->discount_type && $this->discount_type === DiscountType::Fixed) {
            $finalPrice -= $this->discount_value;
        } elseif ($this->discount_type && $this->discount_type === DiscountType::Percentage) {
            $finalPrice -= ($this->price_at_add * ($this->discount_value / 100));
        }

        return max(0, round($finalPrice, 2));
    }


    // Calculates the total price for the item by multiplying the price after discount by the quantity.
    public function getTotalPriceForItemAttribute()
    {
        return round($this->price_after_discount * $this->quantity, 2);
    }
}
