<?php

namespace App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory, UuidTrait;

    protected $fillable = [
        'name',
        'description',
        'price_min',
        'price_max',
        'quantity',
        'image_links',
        'wish_list_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'image_links' => 'array',
    ];

    /**
     * The wish list that the product belongs to
     */
    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(WishList::class, 'wish_list_id');
    }
}
