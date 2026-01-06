<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MultiCompareMessage extends Model
{
    protected $guarded = [];

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'model',
        'all_responses',
    ];

    protected $casts = [
        'all_responses' => 'array',
    ];

    // Custom accessor to ensure JSON is properly handled
    public function getAllResponsesAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    // Custom mutator to ensure JSON is properly stored
    public function setAllResponsesAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['all_responses'] = json_encode($value);
        } else {
            $this->attributes['all_responses'] = $value;
        }
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(MultiCompareConversation::class, 'conversation_id');
    }

    // ✅ CHANGED: Use singular "attachment" with HasOne instead of HasMany
    public function attachments(): HasMany  // ✅ Loads all
    {
        return $this->hasMany(MultiCompareAttachment::class, 'message_id');
    }
}