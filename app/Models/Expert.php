<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expert extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    // App\Models\Expert.php
    public function documents()
    {
        return $this->hasMany(ExpertDocument::class);
    }
    
}
