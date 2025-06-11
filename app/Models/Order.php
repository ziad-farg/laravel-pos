<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'user_id',
        'discount_type',
        'discount_value',
    ];

    protected $appends = [
        'sub_total',
        'total_after_item_discounts',
        'total_after_invoice_discount'
    ];

    protected $casts = [
        'discount_type' => DiscountType::class,
    ];


    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getCustomerName()
    {
        if ($this->customer) {
            return $this->customer->first_name . ' ' . $this->customer->last_name;
        }
        return __('customer.working');
    }

    public function getSubTotalAttribute()
    {
        return round($this->orderItems->sum('price'), 2);
    }

    public function getTotalAfterItemDiscountsAttribute()
    {
        return round($this->orderItems->sum('price_after_discount'), 2);
    }

    public function getTotalAfterInvoiceDiscountAttribute()
    {
        $netTotal = $this->total_after_item_discounts;

        if ($this->discount_type === DiscountType::Fixed) {
            $netTotal -= $this->discount_value;
        } elseif ($this->discount_type === DiscountType::Percentage) {
            $netTotal -= ($this->total_after_item_discounts * ($this->discount_value / 100));
        }

        return max(0, round($netTotal, 2));
    }

    public function total()
    {
        return $this->orderItems->sum('price_after_discount');
    }

    public function formattedTotal()
    {
        return number_format($this->total(), 2);
    }

    public function receivedAmount()
    {
        return $this->payments->sum('amount');
    }

    public function formattedReceivedAmount()
    {
        return number_format($this->receivedAmount(), 2);
    }
}
