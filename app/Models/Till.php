<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Till extends Model
{
    /** @use HasFactory<\Database\Factories\TillFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'cash_handed_over',
        'visa_handed_over',
        'shortage',
        'surplus'
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'cash_handed_over' => 'decimal:2',
        'visa_handed_over' => 'decimal:2',
        'shortage' => 'decimal:2',
        'surplus' => 'decimal:2',
    ];

    protected $appends = [
        'total_cash_sales',
        'total_visa_sales',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Getters for calculated total sales by payment method
    public function getTotalCashSalesAttribute()
    {
        return round($this->payments()->where('payment_method', PaymentMethod::Cash->value)->sum('amount'), 2);
    }

    // Getters for calculated total sales by payment method
    public function getTotalVisaSalesAttribute()
    {
        return round($this->payments()->where('payment_method', PaymentMethod::Visa->value)->sum('amount'), 2);
    }
}
