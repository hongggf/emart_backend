<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'compare_price',
        'sku',
        'image',
        'status',
        'created_by',
        'stock_quantity',
        'low_stock_alert',
    ];

    // Creator relationship
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Category relationship
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // Auto-generate SKU
    protected static function booted()
    {
        static::creating(function ($product) {
            if (empty($product->sku)) {
                $product->sku = 'SKU-' . strtoupper(uniqid());
            }
        });
    }
}