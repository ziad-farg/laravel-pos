<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'cost_price',
        'item_discount_value',
        'item_discount_type',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost_price' => 'decimal:2',
        'item_discount_value' => 'decimal:2',
        'item_discount_type' => DiscountType::class,
    ];

    protected $appends = [
        'final_cost_price',
        'total_cost_for_item',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Calculates and returns the final cost price after applying any discounts, ensuring it is not negative.
    public function getFinalCostPriceAttribute()
    {
        $finalCost = (float) $this->cost_price;

        if ($this->item_discount_type === DiscountType::Fixed) {
            $finalCost -= (float) $this->item_discount_value;
        } elseif ($this->item_discount_type === DiscountType::Percentage) {
            $finalCost -= ($finalCost * ((float) $this->item_discount_value / 100));
        }

        return (float) max(0, $finalCost);
    }

    // Calculates the total cost for the item by multiplying the final cost price by the quantity.
    public function getTotalCostForItemAttribute()
    {
        return (float) ($this->final_cost_price * $this->quantity);
    }
}
