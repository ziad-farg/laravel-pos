<?php


namespace App\Models;

use App\Models\Purchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
    ];

    protected $appends = [
        'full_name',
    ];

    // Get the supplier's full name by concatenating first and last names.
    public function getFullnameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}
