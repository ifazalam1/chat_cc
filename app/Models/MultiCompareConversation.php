<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MultiCompareConversation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'selected_models' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MultiCompareMessage::class, 'conversation_id');
    }

    public function getLastUserMessageAttribute()
    {
        return $this->messages()
            ->where('role', 'user')
            ->latest()
            ->first();
    }

    public function getMessageCountAttribute()
    {
        return $this->messages()->count();
    }

    public function shares()
    {
        return $this->hasMany(MultiCompareConversationShare::class, 'conversation_id');
    }

    public function activeShare()
    {
        return $this->hasOne(MultiCompareConversationShare::class, 'conversation_id')
            ->where('is_public', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
    
    // HEX Code Accessor
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($conversation) {
            if (empty($conversation->hex_code)) {
                $conversation->hex_code = Str::random(32);
            }
        });
    }

    // Add helper method to find by hex_code
    public static function findByHexCode($hexCode)
    {
        return static::where('hex_code', $hexCode)->firstOrFail();
    }
}