<?php

namespace App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefreshToken extends Model
{
    use UuidTrait;

    protected $fillable = [
        'expires_in',
        'user_id',
    ];

    /**
     * Get the parent imageable model.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
