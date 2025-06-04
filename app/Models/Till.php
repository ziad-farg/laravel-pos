<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Till extends Model
{
    /** @use HasFactory<\Database\Factories\TillFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'opened_at', 'closed_at', 'cash', 'visa', 'shortage', 'surplus'];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
