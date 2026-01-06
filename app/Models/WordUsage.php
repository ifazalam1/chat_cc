<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WordUsage extends Model
{
    use HasFactory;
    protected $guarded = [];

    public static function recordMany(array $words): void
    {
        foreach ($words as $word) {
            DB::statement("
                INSERT INTO word_usages (word, count, created_at, updated_at)
                VALUES (:word, 1, now(), now())
                ON CONFLICT (word) DO UPDATE
                SET count = word_usages.count + 1,
                    updated_at = now()
            ", ['word' => strtolower($word)]);
        }
    }

    /**
     * Fetch words that exist in the table
     */
    public static function getExisting(array $words): array
    {
        return DB::table('word_usages')
            ->whereIn('word', array_map('strtolower', $words))
            ->pluck('word')
            ->toArray();
    }
}
