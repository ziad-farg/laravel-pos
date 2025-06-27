<?php

namespace App\Models;

use App\Models\Till;
use App\Models\Image;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\UserCart;
use App\Models\SaleReturn;
use App\Models\PurchaseCart;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['full_name'];

    public function till()
    {
        return $this->hasOne(Till::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function userCart()
    {
        return $this->hasOne(UserCart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function purchaseCarts()
    {
        return $this->hasMany(PurchaseCart::class);
    }

    // Get the user's full name by concatenating first and last names.
    public function getFullnameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
