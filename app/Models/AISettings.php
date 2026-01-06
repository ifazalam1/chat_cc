<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AISettings extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'supports_web_search' => 'boolean',
    ];

    /**
     * Scope: Only active models (status = 1)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

}
