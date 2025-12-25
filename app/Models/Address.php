<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'province',
        'district',
        'street',
        'is_default',
        'created_by',
    ];

    // Owner of address
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Creator (admin or user)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}