<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'invoice_discount_type',
        'invoice_discount_value',
        'status',
        'returned_amount',
    ];

    protected $appends = [
        'order_total',
        'items_sub_total',
        'customer_name',
        'received_amount',
        'raw_items_subtotal',
    ];

    protected $casts = [
        'invoice_discount_type' => DiscountType::class,
        'returned_amount' => 'decimal:2',
    ];


    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    // This method calculates the subtotal of all order items
    public function getItemsSubtotalAttribute()
    {
        $subtotal = $this->orderItems->sum(function ($item) {
            return (float) $item->total_price_for_item;
        });

        return number_format($subtotal, 2, '.', '');
    }

    // This method calculates the total order amount after applying any discounts
    public function getOrderTotalAttribute()
    {
        $netTotal = (float) $this->items_sub_total;

        if ($this->invoice_discount_type === DiscountType::Fixed) {
            $netTotal -= $this->invoice_discount_value;
        } elseif ($this->invoice_discount_type === DiscountType::Percentage) {
            $netTotal -= ((float) $this->items_sub_total * ($this->invoice_discount_value / 100));
        }

        return number_format(max(0, $netTotal), 2, '.', '');
    }

    // This method calculates the total amount received for the order
    public function getReceivedAmountAttribute()
    {
        return number_format($this->payments->sum('amount'), 2, '.', '');
    }


    // This method returns the customer's full name or a default message if no customer is associated
    public function getCustomerNameAttribute()
    {
        if ($this->customer) {
            return $this->customer->first_name . ' ' . $this->customer->last_name;
        }
        return __('customer.working');
    }


    // This method returns the raw subtotal of items without formatting
    public function getRawItemsSubtotalAttribute()
    {
        return (float) $this->orderItems->sum(function ($item) {
            return (float) $item->total_price_for_item;
        });
    }
}
