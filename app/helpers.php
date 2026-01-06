<?php

use App\Models\AIConfig;
use App\Models\AISettings;
use App\Models\ButtonStyle;
use App\Models\PackageHistory;
use App\Models\Plan;
use App\Models\PricingPlan;
use App\Models\RequestModuleFeedback;
use App\Models\SiteSettings;
use App\Models\User;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Cache;


if (!function_exists('calculateCredits')) {
    function calculateCredits($resolution, $quality)
    {
        switch ($resolution) {
            case '512x512':
                return 2;
            case '1024x1024':
                if ($quality === 'hd') {
                    return 8;
                } else {
                    return 4;
                }
            case '1792x1024':
            case '1024x1792':
                if ($quality === 'hd') {
                    return 12;
                } else {
                    return 8;
                }
            default:
                return 4;
        }
    }
}

if (!function_exists('hasEnoughCreditsForSD')) {
    function hasEnoughCreditsForSD($modelVersion)
    {
        $modelCredits = [
            'sd3.5-large' => 7,
            'sd3.5-large-turbo' => 4,
            'sd3.5-medium' => 4,
            'sd-core' => 3,
            'sd-ultra' => 8,
        ];

        $modelVersion = strtolower($modelVersion);
        $requiredCredits = $modelCredits[$modelVersion] ?? 3;

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->credits_left >= $requiredCredits;
    }
}


if (!function_exists('deductCreditsForSD')) {
    function deductCreditsForSD($modelVersion)
    {
        $modelCredits = [
            'sd3.5-large' => 7,
            'sd3.5-large-turbo' => 4,
            'sd3.5-medium' => 4,
            'sd-core' => 3,
            'sd-ultra' => 8,
        ];

        // Normalize the version
        $modelVersion = strtolower($modelVersion);

        // Default to 3 credits if model not recognized
        $credits = $modelCredits[$modelVersion] ?? 3;

        deductUserTokensAndCredits(0, $credits);
    }
}

if (!function_exists('calculateCreditsForGemini')) {
    function calculateCreditsForGemini($model)
    {
        $modelCredits = [
            'imagen-3.0-generate-002' => 3,
            'imagen-3.0-generate-001' => 3,
            'imagen-3.0-fast-generate-001' => 2,
            'imagen-4.0-generate-001' => 4,
            'imagen-4.0-ultra-generate-001' => 6,
            'imagen-4.0-fast-generate-001' => 2,
            // Gemini 2.5 Flash Image model (uses Imagen 4.0 Fast under the hood)
            'gemini-2.5-flash-image' => 2,
        ];

        // Normalize the model name
        $model = strtolower($model);

        // Default to 3 credits if model not recognized
        return $modelCredits[$model] ?? 3;
    }
}

if (!function_exists('hasEnoughCreditsForGemini')) {
    function hasEnoughCreditsForGemini($model, $sampleCount = 1)
    {
        $creditsPerImage = calculateCreditsForGemini($model);
        $requiredCredits = $creditsPerImage * $sampleCount;

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->credits_left >= $requiredCredits;
    }
}

// GET MODEL FROM PACKAGE
if (!function_exists('getUserLastPackageAndModels')) {
    function getUserLastPackageAndModels()
    {
        $user = Auth::user();

        if (!$user) {
            return ['lastPackage' => null, 'aiModels' => [], 'selectedModel' => null, 'freePricingPlan' => null];
        }

        // Get user's last package history
        $lastPackage = PackageHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $freePricingPlan = PricingPlan::where('package_type', 'monthly')
            ->where('slug', 'like', '%free%')
            ->first();

        $aiModels = [];
        $allSettings = AISettings::active()
            ->whereNotNull('openaimodel')
            ->get()
            ->keyBy('openaimodel');

        if ($lastPackage) {
            $pricingPlan = PricingPlan::find($lastPackage->package_id);

            if ($pricingPlan && !empty($pricingPlan->open_id_model)) {
                $models = explode(',', $pricingPlan->open_id_model);
                foreach ($models as $model) {
                    $model = trim($model);
                    if (isset($allSettings[$model])) {
                        $aiModels[] = [
                            'value' => $model,
                            'label' => $allSettings[$model]->displayname,
                        ];
                    }
                }
            }
        } elseif ($freePricingPlan && !empty($freePricingPlan->open_id_model)) {
            $models = explode(',', $freePricingPlan->open_id_model);
            foreach ($models as $model) {
                $model = trim($model);
                if (isset($allSettings[$model])) {
                    $aiModels[] = [
                        'value' => $model,
                        'label' => $allSettings[$model]->displayname,
                    ];
                }
            }
        }

        $selectedModel = $user->selected_model;

        return compact('lastPackage', 'aiModels', 'selectedModel', 'freePricingPlan');
    }
}


// Activity LOG
if (!function_exists('log_activity')) {
    function log_activity($message)
    {
        $user_id = Auth::id(); // Get the authenticated user's ID

        // Get the count of activity logs for the user
        $activityLogCount = \App\Models\ActivityLog::where('user_id', $user_id)->count();

        // If the user already has 10 activity logs, delete the oldest one
        if ($activityLogCount >= 10) {
            \App\Models\ActivityLog::where('user_id', $user_id)
                ->orderBy('created_at', 'asc') // Oldest first
                ->first() // Get the oldest record
                ->delete(); // Delete it
        }

        // Create a new activity log entry
        \App\Models\ActivityLog::create([
            'user_id' => $user_id,
            'message' => $message,
        ]);
    }
}

// SAVE MULTIPLIER TO CACHE
if (!function_exists('getAIModelInfo')) {
    function getAIModelInfo($model = null)
    {   
        log::info('Fetching AI Model Info', ['model' => $model]);

        $all = Cache::remember('ai_model_settings_map', now()->addHours(12), function () {
            return AISettings::all()
                ->keyBy('openaimodel')
                ->map(function ($item) {
                return [
                    'displayname' => $item->displayname,
                    'cost_per_m_tokens' => (float) $item->cost_per_m_tokens,
                    'token_multiplier' => (float) $item->token_multiplier,
                    'modules' => json_decode($item->modules, true) ?? [],
                    ];
                })->toArray();
        });

        return $model ? ($all[$model] ?? null) : $all;
    }
}

// DEDUCT TOKENS
if (!function_exists('deductUserTokensAndCredits')) {
    function deductUserTokensAndCredits(int $tokens = 0, int $credits = 0, ?string $model = null)
    {
        log::info('Inside Deduct User Tokens and Credits', [
            'tokens' => $tokens,
            'credits' => $credits,
            'model' => $model
        ]);
        $user_id = Auth::user()->id;
        $user = User::findOrFail($user_id);

        if (!$user) {
            return "User not authenticated";
        }

        $date = now()->toDateString();

        $year = now()->year;
        $month = now()->month;
        $day = now()->day;

        // Find or create daily usage record
        $dailyUsage = \App\Models\UserMonthlyUsage::firstOrCreate(
            [
                'user_id' => $user_id,
                'year' => $year,
                'month' => $month,
                'day' => $day,
            ],
            [
                'tokens_used' => 0,
                'credits_used' => 0,
            ]
        );

        // ✅ Apply multiplier
        $multiplier = $model ? getModelMultiplier($model) : 1.0;
        Log::info('Model Multiplier 228:', ['multiplier' => $multiplier]);
        $adjustedTokens = (int) ceil($tokens * $multiplier);

        // Deduct tokens
       if ($user->tokens_left >= $adjustedTokens) {
            // Normal case: user has enough tokens
            $user->tokens_left = max(0, $user->tokens_left - $adjustedTokens);
            $user->tokens_used += $adjustedTokens;
            $dailyUsage->tokens_used += $adjustedTokens;
            $usedTokens = $adjustedTokens; // For per-model usage
        } else {
            // Partial deduction: use what's left, rest goes to free tokens
            $usedFromTokens = $user->tokens_left;
            $usedFromFree = $adjustedTokens - $usedFromTokens;

            $user->tokens_left = 0;
            $user->tokens_used += $usedFromTokens;
            $user->free_tokens_used += $usedFromFree;
            $dailyUsage->tokens_used += $usedFromTokens;
            $usedTokens = $usedFromTokens; // For per-model usage
        }

        // Deduct credits
        // if ($user->credits_left < $credits) {
        //     return "no-credits";
        // }

        $user->credits_left = max(0, $user->credits_left - $credits);
        $user->credits_used += $credits;
        $dailyUsage->credits_used += $credits;

        // Increment images_generated only if credits are used
        if ($credits > 0) {
            $user->images_generated += 1;
        }

         // 5️⃣ Track per-model usage
        if ($model) {
            $modelUsage = \App\Models\UserModelUsage::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'model' => $model,
                    'date' => $date,
                ],
                [
                    'tokens_used' => 0,
                    'credits_used' => 0,
                ]
            );

            $modelUsage->tokens_used += $usedTokens;
            $modelUsage->credits_used += $credits;
            $modelUsage->save();
        }

        // Save changes
        $user->save();
        $dailyUsage->save();

        return "deducted-successfully";
    }
}


// GET FEATURE TOOLTIP
if (!function_exists('getFeatureFieldByType')) {
    /**
     * Get the first non-null value of a given field for a specific type.
     */
    function getFeatureFieldByType(string $type, string $field, $default = null): ?string
    {
        $feature = \App\Models\Feature::where('type', $type)
            ->whereNotNull($field)
            ->first();

        return $feature?->$field ?? $default;
    }
}





// GET MODEL MULTIPLIER
if (!function_exists('getModelMultiplier')) {
    function getModelMultiplier($model)
    {
        $modelInfo = getAIModelInfo($model); // already cached
        Log::info('Model Multiplier 267:', ['modelInfo' => $modelInfo]);

         return isset($modelInfo['token_multiplier']) 
            ? (float) $modelInfo['token_multiplier']
            : 1.0;
    }
}


if (!function_exists('deductImageGenerationTokens')) {
    function deductImageGenerationTokens(int $tokens)
    {
        $user = Auth::user();

        if (!$user) {
            return "unauthorized";
        }

        // Block generation if user has 0 tokens
        if ($user->tokens_left <= 0) {
            return "no-tokens";
        }

        $year = now()->year;
        $month = now()->month;
        $day = now()->day;

        // Get or create daily usage
        $dailyUsage = \App\Models\UserMonthlyUsage::firstOrCreate(
            [
                'user_id' => $user->id,
                'year' => $year,
                'month' => $month,
                'day' => $day,
            ],
            [
                'tokens_used' => 0,
            ]
        );

        // Deduct from tokens_left
        $tokensToDeduct = min($user->tokens_left, $tokens);
        $remainingTokens = $tokens - $tokensToDeduct;

        $user->tokens_left -= $tokensToDeduct;
        $user->tokens_used += $tokensToDeduct;
        $dailyUsage->tokens_used += $tokensToDeduct;

        // Add leftover to free_tokens_used
        if ($remainingTokens > 0) {
            $user->free_tokens_used += $remainingTokens;
        }

         // Count the image generation
        $user->images_generated += 1;

        $user->save();
        $dailyUsage->save();

        return "deducted-successfully";
    }
}

// GET MODEL FROM MODULE
if (!function_exists('getModelForModule')) {
    function getModelForModule(string $module): ?string
    {
        $models = Cache::remember('ai_model_settings_map', now()->addHours(12), function () {
            return AISettings::all()
                ->keyBy('openaimodel')
                ->map(function ($item) {
                    return [
                        'displayname' => $item->displayname,
                        'cost_per_m_tokens' => (float) $item->cost_per_m_tokens,
                        'token_multiplier' => (float) $item->token_multiplier,
                        'modules' => json_decode($item->modules, true) ?? [],
                    ];
                })->toArray();
        });

        foreach ($models as $model => $info) {
            if (in_array($module, $info['modules'])) {
                return $model;
            }
        }

        return null; // No model assigned to this module
    }
}


// CHECK IF THE USER HAS TOKENS
if (!function_exists('checkUserHasCredits')) {
    function checkUserHasCredits(int $requiredCredits = 1)
    {
        $user = Auth::user();

        if (!$user) {
            return [
                'status' => false,
                'message' => 'User not authenticated'
            ];
        }

        if ($user->credits_left < $requiredCredits) {
            return [
                'status' => false,
                'message' => 'No credits left'
            ];
        }

        return [
            'status' => true,
            'message' => 'Sufficient credits'
        ];
    }
}



if (!function_exists('get_days_until_next_reset')) {
    function get_days_until_next_reset($user_id = null)
    {
        if (!$user_id) {
            $user_id = Auth::id();
        }

        $user = User::findOrFail($user_id);
        $packageHistory = $user->packageHistory()->with('package')->get();

        $daysUntilNextReset = null;

        if ($packageHistory->isEmpty()) {
            // Free plan case
            $freePricingPlan = PricingPlan::where('title', 'Free')->first();

            // Calculate the next reset date for free plan
            $now = Carbon::now();
            $registrationDate = $user->created_at;
            $nextResetDate = $registrationDate->copy()->addMonths($registrationDate->diffInMonths($now) + 1);
            $daysUntilNextReset = $now->diffInDays($nextResetDate);
        } else {
            // Paid plan case, handle only the last paid package
            $firstPaidPackage = $packageHistory->last();

            if ($firstPaidPackage) {
                // Calculate the next reset date for the first paid package
                $now = Carbon::now();
                $startDate = $firstPaidPackage->created_at;
                $nextResetDate = $startDate->copy()->addMonths($startDate->diffInMonths($now) + 1);
                $daysUntilNextReset = $now->diffInDays($nextResetDate);
            }
        }

        return $daysUntilNextReset;
    }
}

// Feedback Module Request
if (!function_exists('saveModuleFeedback')) {
    function saveModuleFeedback(string $module, string $text)
    {
        $user_id = Auth::user()->id;

        // Create a new RequestModuleFeedback instance
        $feedback = new RequestModuleFeedback();
        $feedback->user_id = $user_id;
        $feedback->module = $module;
        $feedback->text = $text;
        $feedback->status = "pending";

        // Save the feedback to the database
        if ($feedback->save()) {
            return "feedback-saved-successfully";
        }

        return "failed-to-save-feedback";
    }


    // REPHRASE PROMPT FOR GUEST (NO TOKEN DEDUCTION)
    if (!function_exists('rephrasePromptGuest')) {
        function rephrasePromptGuest($prompt)
        {
            $siteSettings = app('siteSettings');

            // Use default model from settings
            $openaiModel = $siteSettings->default_model ?? 'gpt-4o-mini';

            // Use image rephrase system message from SiteSettings, fallback to a default
            $systemMessage = $siteSettings->image_rephrase_system 
                ?? "You are a professional image prompt assistant. Rephrase the user's image generation prompt to enhance creativity, clarity, and captivation. 
                    Maintain the core theme and details, and return only the improved prompt suitable for generating high-quality images.";

            try {
                $response = OpenAI::chat()->create([
                    'model' => $openaiModel,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemMessage
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ],
                    ],
                ]);

                // Just return the content (no token deduction for guest)
                return $response['choices'][0]['message']['content'] ?? "Unable to rephrase at this time.";

            } catch (\Exception $e) {
                Log::error("Guest rephrase error: " . $e->getMessage());
                return "Error: Unable to rephrase the prompt at this time.";
            }
        }
    }


    if (!function_exists('rephrasePrompt')) {
        function rephrasePrompt($prompt)
        {
            $user = auth()->user();
            $siteSettings = app('siteSettings');

            // Use default model from settings
            $openaiModel = $siteSettings->default_model ?? 'gpt-4o-mini';

            // Use image rephrase system message from SiteSettings, fallback to a default
            $systemMessage = $siteSettings->image_rephrase_system 
                ?? "You are a professional image prompt assistant. Rephrase the user's image generation prompt to enhance creativity, clarity, and captivation. 
                    Maintain the core theme and details, and return only the improved prompt suitable for generating high-quality images.";

            try {
                $response = OpenAI::chat()->create([
                    'model' => $openaiModel,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemMessage
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ],
                    ],
                ]);

                $rephrasedPrompt = $response['choices'][0]['message']['content'];
                $totalTokens = $response['usage']['total_tokens'];

                deductUserTokensAndCredits($totalTokens, 0, $openaiModel);

                return $rephrasedPrompt;

            } catch (Exception $e) {
                Log::error("Error with OpenAI API: " . $e->getMessage());
                return "Error: Unable to rephrase the prompt at this time.";
            }
        }
    }
    
    // REPHRASE PROMPT TOOLS
    if (!function_exists('toolRephrasePrompt')) {
        function toolRephrasePrompt($prompt)
        {
            $user = auth()->user();
            $siteSettings = app('siteSettings');

            // Use default model from settings
            $openaiModel = $siteSettings->default_model ?? 'gpt-4o-mini';

            // Use system message from SiteSettings, fallback to a safe default
            $systemMessage = $siteSettings->edutools_rephrase_system 
                ?? "You are a professional writing assistant. Rephrase the user's prompt to enhance clarity, creativity, and engagement. 
                    Do NOT generate image-related prompts. Only return improved text suitable for writing or brainstorming.";

            try {
                $response = OpenAI::chat()->create([
                    'model' => $openaiModel,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemMessage
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ],
                    ],
                ]);

                $rephrasedPrompt = $response['choices'][0]['message']['content'];
                $totalTokens = $response['usage']['total_tokens'];

                deductUserTokensAndCredits($totalTokens, 0, $openaiModel);

                return $rephrasedPrompt;

            } catch (Exception $e) {
                Log::error("Error with OpenAI API: " . $e->getMessage());
                return "Error: Unable to rephrase the prompt at this time.";
            }
        }
    }

    // AI ACTION TOOLS
    if (!function_exists('toolAIAction')) {
        function toolAIAction($prompt, $action)
        {
            $user = auth()->user();
            $siteSettings = app('siteSettings');

            // Use default model from settings
            $openaiModel = $siteSettings->default_model ?? 'gpt-4o-mini';

            // Get system message based on action
            $systemMessage = getSystemMessageForAction($action);

            try {
                $response = OpenAI::chat()->create([
                    'model' => $openaiModel,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemMessage
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ],
                    ],
                ]);

                $result = $response['choices'][0]['message']['content'];
                $totalTokens = $response['usage']['total_tokens'];

                deductUserTokensAndCredits($totalTokens, 0, $openaiModel);

                return $result;

            } catch (Exception $e) {
                Log::error("Error with OpenAI API: " . $e->getMessage());
                return "Error: Unable to process the request at this time.";
            }
        }
    }

    if (!function_exists('getSystemMessageForAction')) {
        function getSystemMessageForAction($action)
        {
            $aiAction = \App\Models\AIAction::where('action', $action)->first();

            if ($aiAction) {
                // Optional: check siteSettings for override
                $siteSettings = app('siteSettings');
                if ($aiAction->setting_key && isset($siteSettings->{$aiAction->setting_key})) {
                    return $siteSettings->{$aiAction->setting_key};
                }
                log::info('Using AI Action System Message', ['action' => $action, 'message' => $aiAction->default_system_message]);
                return $aiAction->default_system_message;
            }

            return "You are a helpful assistant.";
        }
    }


    if (!function_exists('checkOptimizePrompt')) {
        function checkOptimizePrompt($prompt, $request)
        {
            $optimizePrompt = $request->input('hiddenPromptOptimize') ?? '0';
            
            if ($optimizePrompt == '1') {
                // Call the rephrasePrompt function if optimization is enabled
                return rephrasePrompt($prompt);
            }
    
            // Return the original prompt if optimization is not enabled
            return $prompt;
        }
    }


// Extract Prompt From Image
if (!function_exists('callOpenAIImageAPI')) {
    function callOpenAIImageAPI($base64Image)
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'What’s in this image?'],
                            ['type' => 'image_url', 'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . $base64Image,
                            ]],
                        ],
                    ],
                ],
                'max_completion_tokens' => 300,
            ]);

            return $response;
        } catch (Exception $e) {
            // Handle exceptions, log errors, or return a meaningful message
            return ['error' => $e->getMessage()];
        }
    }
}

// User Activity Log (ADMIN)
if (!function_exists('logActivity')) {
    function logActivity($action, $details = null)
    {
        if (auth()->check()) {
            $userId = auth()->id();
            $role = auth()->user()->role;  // Assuming 'role' is a field in your User model
            
            // Append the role to the details
            $roleText = ($role == 'admin') ? 'Admin' : 'User';  // Adjust role check based on your role values
            $detailsWithRole = $roleText . ' - ' . $details;

            // Insert the new log with dynamic role in details
            UserActivityLog::create([
                'user_id' => $userId,
                'action' => $action,
                'details' => $detailsWithRole,  // Save the role info in details
            ]);

            // Keep only the latest 20 logs for the user
            $excessLogs = UserActivityLog::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->skip(20)
                ->take(PHP_INT_MAX)
                ->pluck('id');

            if ($excessLogs->isNotEmpty()) {
                UserActivityLog::whereIn('id', $excessLogs)->delete();
            }
        }
    }
}

// Streak Helper
if (!function_exists('processDailyLoginStreak')) {

    function processDailyLoginStreak(User $user)
    {
        $settings = SiteSettings::first(); // Holds daily reward settings

        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $lastLogin = $user->last_login_date;

        $rewardCredits = 0;
        $rewardTokens  = 0;

        // CASE 1: First time ever
        if (!$lastLogin) {
            $user->streak_count = 1;
            $rewardCredits = $settings->daily_credit_reward;
            $rewardTokens  = $settings->daily_token_reward;
        }

        // CASE 2: Logged in yesterday → continue streak
        elseif ($lastLogin == $yesterday) {
            $user->streak_count += 1;
            $rewardCredits = $settings->daily_credit_reward;
            $rewardTokens  = $settings->daily_token_reward;
        }

        // CASE 3: Already logged in today → no reward
        elseif ($lastLogin == $today) {
            $rewardCredits = 0;
            $rewardTokens  = 0;
        }

        // CASE 4: Missed days → CHECK STREAK FREEZE
        else {

            // 👉 User missed streak BUT has streak freeze points available
            if ($user->streak_freeze > 0) {

                $user->streak_freeze -= 1;     // consume 1 freeze
                // ❗ STREAK CONTINUES (no reset)

                $user->streak_count += 1;

                $rewardCredits = $settings->daily_credit_reward;
                $rewardTokens  = $settings->daily_token_reward;

            } else {
                // ❗ NO FREEZE → streak resets
                $user->streak_count = 1;
                $rewardCredits = $settings->daily_credit_reward;
                $rewardTokens  = $settings->daily_token_reward;
            }
        }

        // Bonus rewards for streaks
        if ($user->streak_count == 7) {
            $rewardCredits += $settings->streak_bonus_7_days_credits ?? 0;
            $rewardTokens  += $settings->streak_bonus_7_days_tokens ?? 0;
        }

        if ($user->streak_count == 30) {
            $rewardCredits += $settings->streak_bonus_30_days_credits ?? 0;
            $rewardTokens  += $settings->streak_bonus_30_days_tokens ?? 0;
        }

        // Apply rewards safely
        $user->credits_left = $user->credits_left + $rewardCredits;
        $user->tokens_left  = $user->tokens_left + $rewardTokens;

        if ($rewardCredits > 0 || $rewardTokens > 0) {
            $user->total_rewards_claimed = $user->total_rewards_claimed + 1;
        }

        // Update last login
        $user->last_login_date = $today;

        // Save everything once
        $user->save();
    }
}



if (!function_exists('getButtonClass')) {
    function getButtonClass($type)
    {
        return ButtonStyle::where('button_type', $type)->where('is_selected', true)->value('class_name') ?? 'btn btn-default';
    }
}

// Chwck if the user has tokens
if (!function_exists('userHasTokensLeft')) {
    function userHasTokensLeft()
    {
        $user = Auth::user();

        if (!$user) {
            return false; // No user logged in
        }
        
        return $user->tokens_left > 0;
    }
}

// Format Duration (Time Spent)
if (!function_exists('formatDuration')) {
    function formatDuration($seconds)
    {
        $seconds = (int)$seconds;

        if ($seconds < 60) {
            return $seconds . ' sec' . ($seconds !== 1 ? 's' : '');
        }

        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            $remaining = $seconds % 60;
            return $minutes . ' min' . ($minutes !== 1 ? 's' : '') .
                ($remaining > 0 ? " {$remaining} sec" . ($remaining !== 1 ? 's' : '') : '');
        }

        $hours = floor($minutes / 60);
        if ($hours < 24) {
            $remainingMins = $minutes % 60;
            return $hours . ' hr' . ($hours !== 1 ? 's' : '') .
                ($remainingMins > 0 ? " {$remainingMins} min" . ($remainingMins !== 1 ? 's' : '') : '');
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;
        return $days . ' day' . ($days !== 1 ? 's' : '') .
            ($remainingHours > 0 ? " {$remainingHours} hr" . ($remainingHours !== 1 ? 's' : '') : '');
    }
}


// AIConfig
if (!function_exists('aiconfig')) {
    function aiconfig(string $function_name, string $type)
    {
        $query = AIConfig::where('function_name', $function_name);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->first();
    }
}



// FOR API HELPERS
if (!function_exists('deductUserTokensAndCreditsAPI')) {
    function deductUserTokensAndCreditsAPI($user, int $tokens = 0, int $credits = 0)
    {

        Log::info('Inside Deducat API 356');
        if (!$user) {
            return "User not authenticated";
        }

        // If the user has tokens, deduct normally
        if ($user->tokens_left >= $tokens) {
            $user->tokens_left = max(0, $user->tokens_left - $tokens);
            $user->tokens_used = max(0, $user->tokens_used + $tokens);
        } else {
            // If the user has no tokens, track free generations instead
            $user->free_tokens_used += $tokens;
        }

        // Deduct credits if required
        if ($user->credits_left < $credits) {
            return "no-credits";
        }

        $user->credits_left = max(0, $user->credits_left - $credits);
        $user->credits_used = max(0, $user->credits_used + $credits);

        // Increment images_generated only if credits are used
        if ($credits > 0) {
            $user->images_generated += 1;
        }

        // Save changes
        $user->save();

        return "deducted-successfully";
    }
}

if (!function_exists('approximateTokenCount')) {
    function approximateTokenCount($text) {
        return intval((strlen($text) / 4) * 1.05); // add small buffer
    }
}

    

}






