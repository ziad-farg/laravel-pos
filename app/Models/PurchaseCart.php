<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'cost_price_at_add',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost_price_at_add' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_type' => DiscountType::class,
    ];

    protected $appends = [
        'price_after_discount',
        'total_price_for_item',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }


    // Calculates and returns the item's price after applying the specified discount, ensuring it is not negative.
    public function getPriceAfterDiscountAttribute()
    {
        $finalPrice = (float) $this->cost_price_at_add;

        if ($this->discount_type && $this->discount_type === DiscountType::Fixed) {
            $finalPrice -= (float) $this->discount_value;
        } elseif ($this->discount_type && $this->discount_type === DiscountType::Percentage) {
            $finalPrice -= ($finalPrice * ((float) $this->discount_value / 100));
        }

        return (float) max(0, $finalPrice);
    }

    // Calculates the total price for the item by multiplying the price after discount by the quantity.
    public function getTotalPriceForItemAttribute()
    {
        return (float) ($this->price_after_discount * $this->quantity);
    }
}
