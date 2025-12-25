<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'address_id',
        'subtotal',
        'shipping_fee',
        'discount',
        'total_amount',
        'status',
        'payment_status',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}