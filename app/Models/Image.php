<?php

namespace App\Models;

use App\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use UuidTrait;

    protected $fillable = [
        'name',
        'original_name',
        'format',
        'folder',
    ];

    /**
     * Get the parent imageable model.
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
