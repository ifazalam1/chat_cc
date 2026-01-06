<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'ai_models' => 'array',
        'ai_templates' => 'array',
        'experts' => 'array',
        'edu_tools' => 'array',
        'additional_features' => 'array',
    ];
   
    public function features()
    {
        return $this->belongsToMany(Feature::class);
    }
}
