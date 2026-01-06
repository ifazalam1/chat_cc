<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MultiCompareConversationShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'share_token',
        'created_by',
        'is_public',
        'expires_at',
        'view_count'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(MultiCompareConversation::class, 'conversation_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate a unique share token
     */
    public static function generateToken()
    {
        do {
            $token = Str::random(32);
        } while (self::where('share_token', $token)->exists());

        return $token;
    }

    /**
     * Check if share is valid (not expired)
     */
    public function isValid()
    {
        if (!$this->is_public) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('view_count');
    }
}