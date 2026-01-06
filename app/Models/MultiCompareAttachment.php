<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MultiCompareAttachment extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $fillable = [
        'message_id',
        'file_path',
        'file_name',
        'file_type',
    ];

    // ✅ ADD: Relationship back to message
    public function message(): BelongsTo
    {
        return $this->belongsTo(MultiCompareMessage::class, 'message_id');
    }
}