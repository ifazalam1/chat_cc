<?php

namespace App\Services\AI;

use App\Models\Keyword;
use App\Models\WordUsage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QueryComplexityAnalyzer
{
    /**
     * Analyze query complexity and return a score (1-100)
     * Higher score = more complex query = needs better model
     */
    public static function analyze(string $query): int
    {
        $queryLower = strtolower($query);
        $words = str_word_count($queryLower, 1);

        // 🔹 Fetch keyword groups dynamically (cached)
        $keywords = self::getKeywordGroups();

        // 🔹 Extract category arrays safely
        $codeKeywords = $keywords->has('code') ? $keywords['code']->pluck('word')->toArray() : [];
        $mathKeywords = $keywords->has('math') ? $keywords['math']->pluck('word')->toArray() : [];
        $academicKeywords = $keywords->has('academic') ? $keywords['academic']->pluck('word')->toArray() : [];

        
        // Merge all keywords into one list for storage
        $allKeywords = array_merge($codeKeywords, $mathKeywords, $academicKeywords);

        // Only keep words that are in your keyword lists
        $matchedWords = array_filter($words, fn($word) => in_array($word, $allKeywords));

        // 🔹 Step 3: Store only matched keywords
        WordUsage::recordMany($matchedWords);

        $keywordScore = 30;

        foreach ($words as $word) {
            if (in_array($word, $codeKeywords)) $keywordScore += 10;
            if (in_array($word, $mathKeywords)) $keywordScore += 8;
            if (in_array($word, $academicKeywords)) $keywordScore += 5;
        }

        // 🔹 Step 3: Individual analysis components
        $scores = [
            'length' => self::analyzeLengthComplexity($query),
            'technical' => self::analyzeTechnicalComplexity($query),
            'linguistic' => self::analyzeLinguisticComplexity($query),
            'question_type' => self::analyzeQuestionType($query),
            'context' => self::analyzeContextRequirements($query),
            'keywords' => $keywordScore
        ];

        // 🔹 Step 4: Weighted average
        $finalScore = (
            ($scores['length'] * 0.20) +
            ($scores['technical'] * 0.25) +
            ($scores['linguistic'] * 0.20) +
            ($scores['question_type'] * 0.15) +
            ($scores['context'] * 0.15) + // adjusted to fit keywords
            ($scores['keywords'] * 0.05)   // small weight for keywords
        );

        $complexity = (int) round($finalScore);

        Log::info('Query Complexity Analysis', [
            'query_preview' => substr($query, 0, 100),
            'scores' => $scores,
            'final_complexity' => $complexity
        ]);

        return min(100, $complexity);
    }

    /**
     * Analyze complexity based on query length
     */
    private static function analyzeLengthComplexity(string $query): int
    {
        $length = strlen($query);
        $wordCount = str_word_count($query);

        // Very short queries (< 50 chars) = Simple
        if ($length < 50) return 20;

        // Short queries (50-150 chars) = Low-Medium
        if ($length < 150) return 35;

        // Medium queries (150-300 chars) = Medium
        if ($length < 300) return 50;

        // Long queries (300-500 chars) = Medium-High
        if ($length < 500) return 65;

        // Very long queries (> 500 chars) = High
        return 80;
    }

    /**
     * Detect technical/specialized content
     */
    private static function analyzeTechnicalComplexity(string $query): int
    {
        $query_lower = strtolower($query);
        $score = 30; // Base score

        $keywords = self::getKeywordGroups();

        $codeKeywords = $keywords->has('code') ? $keywords['code']->pluck('word')->toArray() : [];
        $mathKeywords = $keywords->has('math') ? $keywords['math']->pluck('word')->toArray() : [];
        $academicKeywords = $keywords->has('academic') ? $keywords['academic']->pluck('word')->toArray() : [];

        // Check for code patterns
        if (preg_match('/```|`[^`]+`|{|}|\[|\]|<\/>/', $query)) {
            $score += 25;
        }

        // Check for technical keywords
        $codeMatches = 0;
        foreach ($codeKeywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                $codeMatches++;
            }
        }
        if ($codeMatches >= 3) $score += 20;
        elseif ($codeMatches >= 1) $score += 10;

        // Check for math keywords
        $mathMatches = 0;
        foreach ($mathKeywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                $mathMatches++;
            }
        }
        if ($mathMatches >= 2) $score += 20;
        elseif ($mathMatches >= 1) $score += 10;

        // Check for academic keywords
        $academicMatches = 0;
        foreach ($academicKeywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                $academicMatches++;
            }
        }
        if ($academicMatches >= 2) $score += 15;
        elseif ($academicMatches >= 1) $score += 8;

        return min(100, $score);
    }

    /**
     * Analyze linguistic complexity
     */
    private static function analyzeLinguisticComplexity(string $query): int
    {
        $score = 30;

        // Check for complex sentence structures
        $sentenceCount = substr_count($query, '.') + substr_count($query, '?') + substr_count($query, '!');
        if ($sentenceCount >= 5) $score += 20;
        elseif ($sentenceCount >= 3) $score += 10;

        // Check for complex conjunctions
        $complexWords = ['however', 'therefore', 'moreover', 'furthermore', 'consequently', 'nevertheless'];
        foreach ($complexWords as $word) {
            if (stripos($query, $word) !== false) {
                $score += 5;
            }
        }

        // Check for technical terminology density
        $words = str_word_count($query);
        $capitalizedWords = preg_match_all('/\b[A-Z][a-z]+/', $query);
        if ($words > 0 && ($capitalizedWords / $words) > 0.2) {
            $score += 15;
        }

        // Check for multiple questions
        $questionMarks = substr_count($query, '?');
        if ($questionMarks >= 3) $score += 15;
        elseif ($questionMarks >= 2) $score += 10;

        return min(100, $score);
    }

    /**
     * Analyze question type complexity
     */
    private static function analyzeQuestionType(string $query): int
    {
        $query_lower = strtolower($query);

        // Simple factual questions = Low complexity
        if (preg_match('/^(what is|who is|when did|where is)/i', $query)) {
            return 25;
        }

        // Definition/explanation requests = Low-Medium
        if (preg_match('/\b(define|explain|describe|tell me about)\b/i', $query)) {
            return 40;
        }

        // How-to questions = Medium
        if (preg_match('/^how (to|do|can|does)/i', $query)) {
            return 50;
        }

        // Analysis/comparison = Medium-High
        if (preg_match('/\b(analyze|compare|contrast|evaluate|assess)\b/i', $query)) {
            return 70;
        }

        // Creative/open-ended = High
        if (preg_match('/\b(create|design|develop|propose|suggest|recommend)\b/i', $query)) {
            return 75;
        }

        // Multi-step or problem-solving = Very High
        if (preg_match('/\b(solve|optimize|improve|debug|fix|troubleshoot)\b/i', $query)) {
            return 85;
        }

        return 50; // Default medium complexity
    }

    /**
     * Analyze context requirements
     */
    private static function analyzeContextRequirements(string $query): int
    {
        $score = 30;

        // References to previous context
        if (preg_match('/\b(as mentioned|previously|earlier|above|before|continue|also)\b/i', $query)) {
            $score += 20;
        }

        // Multiple topics/subjects
        $topics = preg_match_all('/\b(and|or|regarding|about|concerning)\b/i', $query);
        if ($topics >= 3) $score += 15;
        elseif ($topics >= 1) $score += 8;

        // Requires external knowledge
        if (preg_match('/\b(current|latest|recent|today|now|2024|2025)\b/i', $query)) {
            $score += 15;
        }

        // Requires reasoning/inference
        if (preg_match('/\b(why|because|reason|cause|effect|impact|implication)\b/i', $query)) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * Get query features for pattern matching
     */
    public static function extractFeatures(string $query): array
    {
        return [
            'length' => strlen($query),
            'word_count' => str_word_count($query),
            'has_code' => preg_match('/```|`[^`]+`/', $query) > 0,
            'has_math' => preg_match('/\b(equation|formula|calculate)\b/i', $query) > 0,
            'has_multiple_questions' => substr_count($query, '?') > 1,
            'is_creative' => preg_match('/\b(create|design|write|compose)\b/i', $query) > 0,
            'requires_web_search' => preg_match('/\b(current|latest|recent|today|news)\b/i', $query) > 0,
        ];
    }

    /**
     * Get query hash for pattern matching
     */
    public static function getQueryHash(string $query): string
    {
        // Normalize query for better pattern matching
        $normalized = strtolower(trim($query));
        $normalized = preg_replace('/\s+/', ' ', $normalized); // Normalize whitespace
        
        return md5($normalized);
    }

    private static function getKeywordGroups()
    {
        return Cache::remember('keywords_grouped', 3600, function () {
            return Keyword::all()->groupBy('category');
        });
    }
}