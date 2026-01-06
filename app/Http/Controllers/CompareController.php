<?php

namespace App\Http\Controllers;


use App\Models\AIAction;
use App\Models\AISettings;
use App\Models\ChatAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Expert;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\CreateBlockBlobOptions;
use Illuminate\Support\Str;
use App\Models\MultiCompareConversation;
use App\Models\MultiCompareMessage;
use App\Models\MultiCompareAttachment;
use App\Models\MultiCompareConversationShare;
use Spatie\PdfToImage\Pdf;

use Parsedown;

use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use TCPDF;
use PhpOffice\PhpWord\PhpWord;

class CompareController extends Controller
{
    // Convert messages format for Claude API
    private function convertMessagesToClaudeFormat($messages)
    {
        $systemMessages = [];
        $claudeMessages = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemMessages[] = $msg['content'];
                continue;
            }

            // Handle image content for Claude
            if (isset($msg['content']) && is_array($msg['content'])) {
                $claudeContent = [];
                foreach ($msg['content'] as $content) {
                    if ($content['type'] === 'text') {
                        $claudeContent[] = [
                            'type' => 'text',
                            'text' => $content['text']
                        ];
                    } elseif ($content['type'] === 'image_url') {
                        // Claude requires base64 encoded images
                        $imageUrl = $content['image_url']['url'];
                        
                        // Fetch and convert to base64
                        try {
                            // ✅ FIX: Use curl with proper error handling for URLs with special characters
                            $ch = curl_init($imageUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                            $imageData = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            $curlError = curl_error($ch);
                            curl_close($ch);
                            
                            if ($imageData === false || $httpCode !== 200) {
                                Log::error('Failed to fetch image for Claude', [
                                    'url' => $imageUrl,
                                    'http_code' => $httpCode,
                                    'curl_error' => $curlError
                                ]);
                                continue; // Skip this image but continue with others
                            }
                            
                            $base64 = base64_encode($imageData);
                            $mimeType = $this->getImageMimeType($imageUrl);
                            
                            $claudeContent[] = [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mimeType,
                                    'data' => $base64
                                ]
                            ];
                        } catch (\Exception $e) {
                            Log::error('Error converting image for Claude: ' . $e->getMessage());
                        }
                    }
                }
                
                $claudeMessages[] = [
                    'role' => $msg['role'],
                    'content' => $claudeContent
                ];
            } else {
                // Simple text message
                $claudeMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }

        return [
            'system' => !empty($systemMessages) ? implode("\n\n", $systemMessages) : null,
            'messages' => $claudeMessages
        ];
    }

    private function getImageMimeType($url)
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        return $mimeTypes[$extension] ?? 'image/jpeg';
    }

    // ✅ NEW: Helper method to get suggested model based on complexity
    private function getSuggestedModelFromComplexity(int $score, string $currentModel, $user, bool $requireWebSearch = false): string
    {
        // Determine the provider of the current model
        $currentProvider = $this->getModelProvider($currentModel);

        Log::debug('Provider-aware model suggestion starting', [
            'current_model' => $currentModel,
            'current_provider' => $currentProvider,
            'complexity_score' => $score,
            'require_web_search' => $requireWebSearch
        ]);

        // Get all active AI models from settings with their tiers
        $aiSettings = DB::table('a_i_settings')
            ->where('status', 1)
            ->orderBy('tier')
            ->orderBy('cost_per_m_tokens', 'asc')
            ->get();

        // Filter by provider and web search support
        $aiSettings = $aiSettings->filter(function($setting) use ($currentProvider, $requireWebSearch) {
            $matchesProvider = $this->getModelProvider($setting->openaimodel) === $currentProvider;
            $supportsWebSearch = !$requireWebSearch || ($setting->supports_web_search ?? false);
            return $matchesProvider && $supportsWebSearch;
        });
        
        // Group models by tier
        $modelsByTier = $aiSettings->groupBy('tier');
        
        // Determine suggested tier based on complexity score
        $suggestedTier = $this->getTierFromComplexity($score);
        
        Log::debug('Model suggestion process', [
            'complexity_score' => $score,
            'suggested_tier' => $suggestedTier,
            'current_provider' => $currentProvider,
            'user_plan' => $user->plan?->name,
            'user_accessible_models' => $user->aiModels()
        ]);
        
        // Get user's accessible models (filtered by provider)
        $userModels = $this->getUserModelsByProvider($user, $currentProvider);
        
        // If user has no accessible models from this provider, return current model
        if (empty($userModels)) {
            Log::warning('User has no accessible models from provider', [
                'user_id' => $user->id,
                'provider' => $currentProvider
            ]);
            return $currentModel;
        }
        
        // ✅ NEW: Find CHEAPEST model in suggested tier that user has access to
        if (isset($modelsByTier[$suggestedTier])) {
            $cheapestModel = null;
            $lowestCost = PHP_FLOAT_MAX;
            
            foreach ($modelsByTier[$suggestedTier] as $aiSetting) {
                $model = $aiSetting->openaimodel;
                $cost = (float) ($aiSetting->cost_per_m_tokens ?? PHP_FLOAT_MAX);
                
                // Check if user has access to this model AND it's cheaper
                if ($user->hasModelAccess($model) && $cost < $lowestCost) {
                    $cheapestModel = $model;
                    $lowestCost = $cost;
                }
            }
            
            if ($cheapestModel) {
                Log::info('Found cheapest model in suggested tier (same provider)', [
                    'model' => $cheapestModel,
                    'provider' => $currentProvider,
                    'tier' => $suggestedTier,
                    'cost_per_m_tokens' => $lowestCost,
                    'complexity' => $score
                ]);
                return $cheapestModel;
            }
        }
        
        // Try lower tiers if user doesn't have access to suggested tier
        $tierHierarchy = ['basic', 'standard', 'advanced', 'premium'];
        $suggestedTierIndex = array_search($suggestedTier, $tierHierarchy);
        
        // Search downwards from suggested tier
        for ($i = $suggestedTierIndex - 1; $i >= 0; $i--) {
            $lowerTier = $tierHierarchy[$i];
            
            if (isset($modelsByTier[$lowerTier])) {
                $cheapestModel = null;
                $lowestCost = PHP_FLOAT_MAX;
                
                foreach ($modelsByTier[$lowerTier] as $aiSetting) {
                    $model = $aiSetting->openaimodel;
                    $cost = (float) ($aiSetting->cost_per_m_tokens ?? PHP_FLOAT_MAX);
                    
                    if ($user->hasModelAccess($model) && $cost < $lowestCost) {
                        $cheapestModel = $model;
                        $lowestCost = $cost;
                    }
                }
                
                if ($cheapestModel) {
                    Log::info('Falling back to cheapest model in lower tier (same provider)', [
                        'model' => $cheapestModel,
                        'provider' => $currentProvider,
                        'tier' => $lowerTier,
                        'cost_per_m_tokens' => $lowestCost,
                        'original_tier' => $suggestedTier
                    ]);
                    return $cheapestModel;
                }
            }
        }
        
        // Fallback to current model if accessible, otherwise first available model from provider
        if ($user->hasModelAccess($currentModel)) {
            Log::info('Using current model as fallback (provider match)', [
                'model' => $currentModel,
                'provider' => $currentProvider
            ]);
            return $currentModel;
        }
        
        // Last resort: return cheapest accessible model from same provider
        $fallbackModel = $this->getCheapestModelFromList($userModels, $currentProvider);
        Log::warning('Using cheapest accessible model from provider as last resort', [
            'model' => $fallbackModel,
            'provider' => $currentProvider
        ]);
        return $fallbackModel;
    }

    private function getModelProvider(string $modelName): string
    {
        // Try to get from database first
        $modelSettings = \App\Models\AISettings::active()
            ->where('openaimodel', $modelName)
            ->first();
        
        if ($modelSettings && $modelSettings->provider) {
            return $modelSettings->provider;
        }
        
        // Fallback: If not in database, try to guess (for backwards compatibility)
        $modelLower = strtolower($modelName);
        
        if (str_contains($modelLower, 'claude')) {
            return 'claude';
        } elseif (str_contains($modelLower, 'gemini')) {
            return 'gemini';
        } elseif (str_contains($modelLower, 'grok')) {
            return 'grok';
        } else {
            return 'openai'; // Default
        }
    }

    /**
     * Check if model is from specific provider
     */
    private function isClaudeModel($model): bool
    {
        return $this->getModelProvider($model) === 'claude';
    }

    private function isGeminiModel($model): bool
    {
        return $this->getModelProvider($model) === 'gemini';
    }

    private function isGrokModel($model): bool
    {
        return $this->getModelProvider($model) === 'grok';
    }

    private function isOpenAIModel($model): bool
    {
        return $this->getModelProvider($model) === 'openai';
    }

    /**
     * Helper to check if model supports image generation
     * All providers support it except Claude
     */
    private function supportsImageGeneration($model): bool
    {
        $provider = $this->getModelProvider($model);
        return $provider !== 'claude';
    }

    /**
     * Get maximum number of images allowed per request for a model
     * Different models have different limits
     */
    private function getMaxImagesForModel($model): int
    {
        if ($this->isClaudeModel($model)) {
            // Claude 3.5 Sonnet and Opus support up to 20 images per message
            return 20;
        } elseif ($this->isGeminiModel($model)) {
            // Gemini supports up to 16 images per request
            return 16;
        } elseif ($this->isGrokModel($model)) {
            // Grok supports up to 10 images per request
            return 10;
        } elseif ($this->isOpenAIModel($model)) {
            // GPT-4 Vision supports up to 10 images per request
            return 10;
        }
        
        // Default conservative limit
        return 5;
    }

    /**
     * Validate image count against model limits
     * Returns array with 'valid' boolean and 'error' message if invalid
     */
    private function validateImageCountForModels($imageCount, $selectedModels): array
    {
        if ($imageCount === 0) {
            return ['valid' => true];
        }

        $errors = [];
        foreach ($selectedModels as $model) {
            $maxImages = $this->getMaxImagesForModel($model);
            if ($imageCount > $maxImages) {
                $errors[] = "Model '{$model}' supports maximum {$maxImages} images, but {$imageCount} were provided.";
            }
        }

        if (!empty($errors)) {
            return [
                'valid' => false,
                'error' => implode(' ', $errors)
            ];
        }

        return ['valid' => true];
    }

    private function getUserModelsByProvider($user, string $provider): array
    {
        $allUserModels = $user->aiModels();
        
        // Filter models by provider using model detection methods
        $providerModels = array_filter($allUserModels, function($model) use ($provider) {
            return $this->getModelProvider($model) === $provider;
        });
        
        // Re-index array
        $providerModels = array_values($providerModels);
        
        Log::debug('Filtered user models by provider', [
            'provider' => $provider,
            'total_models' => count($allUserModels),
            'provider_models' => count($providerModels),
            'models' => $providerModels
        ]);
        
        return $providerModels;
    }

    private function getTierFromComplexity(int $score): string
    {
        if ($score < 30) {
            return 'basic';
        } elseif ($score < 50) {
            return 'standard';
        } elseif ($score < 70) {
            return 'advanced';
        } else {
            return 'premium';
        }
    }

    // ✅ NEW: Helper method to get suggested model from ANY provider (cross-provider selection)
    private function getSuggestedModelFromAnyProvider(int $score, string $currentModel, $user, bool $requireWebSearch = false): string
    {
        Log::debug('Cross-provider model suggestion starting', [
            'current_model' => $currentModel,
            'complexity_score' => $score,
            'user_plan' => $user->plan?->name,
            'require_web_search' => $requireWebSearch
        ]);

        // Get all active AI models from settings with their tiers and costs
        $query = DB::table('a_i_settings')
            ->where('status', 1)
            ->orderBy('tier')
            ->orderBy('cost_per_m_tokens', 'asc'); // ✅ Order by cost (cheapest first)

        // Filter for web search support if required
        if ($requireWebSearch) {
            $query->where('supports_web_search', true);
        }

        $aiSettings = $query->get();
        
        // Group models by tier (across ALL providers)
        $modelsByTier = $aiSettings->groupBy('tier');
        
        // Determine suggested tier based on complexity score
        $suggestedTier = $this->getTierFromComplexity($score);
        
        Log::debug('Cross-provider model selection process', [
            'complexity_score' => $score,
            'suggested_tier' => $suggestedTier,
            'user_plan' => $user->plan?->name,
            'user_accessible_models' => $user->aiModels()
        ]);
        
        // Get user's accessible models (from ALL providers)
        $userModels = $user->aiModels();
        
        // If user has no accessible models, return current model
        if (empty($userModels)) {
            Log::warning('User has no accessible models', [
                'user_id' => $user->id
            ]);
            return $currentModel;
        }
        
        // ✅ NEW: Find CHEAPEST model in suggested tier that user has access to (ANY provider)
        if (isset($modelsByTier[$suggestedTier])) {
            $cheapestModel = null;
            $lowestCost = PHP_FLOAT_MAX;
            
            foreach ($modelsByTier[$suggestedTier] as $aiSetting) {
                $model = $aiSetting->openaimodel;
                $cost = (float) ($aiSetting->cost_per_m_tokens ?? PHP_FLOAT_MAX);
                
                // Check if user has access to this model AND it's cheaper
                if ($user->hasModelAccess($model) && $cost < $lowestCost) {
                    $cheapestModel = $model;
                    $lowestCost = $cost;
                }
            }
            
            if ($cheapestModel) {
                Log::info('Found cheapest model in suggested tier (cross-provider)', [
                    'model' => $cheapestModel,
                    'provider' => $this->getModelProvider($cheapestModel),
                    'tier' => $suggestedTier,
                    'cost_per_m_tokens' => $lowestCost,
                    'complexity' => $score
                ]);
                return $cheapestModel;
            }
        }
        
        // ✅ UPDATED: Try lower tiers, selecting CHEAPEST in each tier
        $tierHierarchy = ['basic', 'standard', 'advanced', 'premium'];
        $suggestedTierIndex = array_search($suggestedTier, $tierHierarchy);
        
        // Search downwards from suggested tier
        for ($i = $suggestedTierIndex - 1; $i >= 0; $i--) {
            $lowerTier = $tierHierarchy[$i];
            
            if (isset($modelsByTier[$lowerTier])) {
                $cheapestModel = null;
                $lowestCost = PHP_FLOAT_MAX;
                
                foreach ($modelsByTier[$lowerTier] as $aiSetting) {
                    $model = $aiSetting->openaimodel;
                    $cost = (float) ($aiSetting->cost_per_m_tokens ?? PHP_FLOAT_MAX);
                    
                    if ($user->hasModelAccess($model) && $cost < $lowestCost) {
                        $cheapestModel = $model;
                        $lowestCost = $cost;
                    }
                }
                
                if ($cheapestModel) {
                    Log::info('Falling back to cheapest model in lower tier (cross-provider)', [
                        'model' => $cheapestModel,
                        'provider' => $this->getModelProvider($cheapestModel),
                        'tier' => $lowerTier,
                        'cost_per_m_tokens' => $lowestCost,
                        'original_tier' => $suggestedTier
                    ]);
                    return $cheapestModel;
                }
            }
        }
        
        // Fallback to current model if accessible, otherwise cheapest available model
        if ($user->hasModelAccess($currentModel)) {
            Log::info('Using current model as fallback (cross-provider)', [
                'model' => $currentModel,
                'provider' => $this->getModelProvider($currentModel)
            ]);
            return $currentModel;
        }
        
        // Last resort: return cheapest accessible model from any provider
        $fallbackModel = $this->getCheapestModelFromList($userModels);
        Log::warning('Using cheapest accessible model as last resort (cross-provider)', [
            'model' => $fallbackModel,
            'provider' => $this->getModelProvider($fallbackModel)
        ]);
        return $fallbackModel;
    }
   
    // ✅ NEW: Helper method to find cheapest model from a list of model names
    private function getCheapestModelFromList(array $modelNames, ?string $provider = null): string
    {
        if (empty($modelNames)) {
            return '';
        }
        
        $query = DB::table('a_i_settings')
            ->whereIn('openaimodel', $modelNames)
            ->where('status', 1);
        
        // Optionally filter by provider
        if ($provider) {
            // Filter by provider after fetching
            $models = $query->get()->filter(function($setting) use ($provider) {
                return $this->getModelProvider($setting->openaimodel) === $provider;
            });
        } else {
            $models = $query->get();
        }
        
        if ($models->isEmpty()) {
            return $modelNames[0]; // Fallback to first model
        }
        
        // Find the model with lowest cost_per_m_tokens
        $cheapest = $models->sortBy('cost_per_m_tokens')->first();
        
        Log::debug('Selected cheapest model from list', [
            'models_considered' => $modelNames,
            'provider' => $provider ?? 'any',
            'selected' => $cheapest->openaimodel,
            'cost_per_m_tokens' => $cheapest->cost_per_m_tokens
        ]);
        
        return $cheapest->openaimodel;
    }

    // Convert messages format for Gemini API
    private function convertMessagesToGeminiFormat($messages)
    {
        $systemInstructions = [];
        $geminiContents = [];
        $documentCount = 0;
        
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                // ✅ Track document system messages
                if (strpos($msg['content'], 'Content from') !== false) {
                    $documentCount++;
                }
                $systemInstructions[] = $msg['content'];
                continue;
            }
            
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            
            // Handle multimodal content (images)
            if (isset($msg['content']) && is_array($msg['content'])) {
                $parts = [];
                
                foreach ($msg['content'] as $content) {
                    if ($content['type'] === 'text') {
                        $parts[] = [
                            'text' => $content['text']
                        ];
                    } elseif ($content['type'] === 'image_url') {
                        // Gemini requires base64 encoded images
                        $imageUrl = $content['image_url']['url'];
                        
                        try {
                            // ✅ FIX: Use curl with proper error handling for URLs with special characters
                            $ch = curl_init($imageUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                            $imageData = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            $curlError = curl_error($ch);
                            curl_close($ch);
                            
                            if ($imageData === false || $httpCode !== 200) {
                                Log::error('Failed to fetch image for Gemini', [
                                    'url' => $imageUrl,
                                    'http_code' => $httpCode,
                                    'curl_error' => $curlError
                                ]);
                                continue; // Skip this image but continue with others
                            }
                            
                            $base64 = base64_encode($imageData);
                            $mimeType = $this->getImageMimeType($imageUrl);
                            
                            $parts[] = [
                                'inlineData' => [
                                    'mimeType' => $mimeType,
                                    'data' => $base64
                                ]
                            ];
                        } catch (\Exception $e) {
                            Log::error('Error converting image for Gemini: ' . $e->getMessage());
                        }
                    }
                }
                
                $geminiContents[] = [
                    'role' => $role,
                    'parts' => $parts
                ];
            } else {
                // Simple text message
                $geminiContents[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $msg['content']]
                    ]
                ];
            }
        }
        
        $combinedSystemInstruction = !empty($systemInstructions) ? implode("\n\n", $systemInstructions) : null;
        
        // ✅ Log document content for debugging
        if ($documentCount > 0) {
            Log::info('Gemini format conversion', [
                'total_system_messages' => count($systemInstructions),
                'document_messages' => $documentCount,
                'system_instruction_length' => strlen($combinedSystemInstruction ?? ''),
                'has_document_content' => strpos($combinedSystemInstruction ?? '', 'Content from') !== false
            ]);
        }
        
        return [
            'systemInstruction' => $combinedSystemInstruction,
            'contents' => $geminiContents
        ];
    }

    // Multi-Compare Interface
    public function index($hexCode = null)
    {
        logActivity('Multi Chat', 'Accessed Multi-Model Chat Comparison Interface');
        $experts = \App\Models\Expert::whereIn('domain', ['expert-chat', 'ai-tutor'])->get();
        $groupedExperts = $experts->groupBy('domain')->map(function ($domainGroup) {
            return $domainGroup->groupBy('category');
        });

        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        $siteSettings = app('siteSettings');
        $defaultModel = $siteSettings->default_model ?? null;
        $tokenLimit = (int) $siteSettings->token_left_conditional;
        $hasLowTokens = $user->tokens_left < $tokenLimit;

        if ($user->hasRole('admin')) {
            $availableModels = \App\Models\AISettings::active()
                ->whereNotNull('openaimodel')
                ->whereNotNull('provider')
                ->select('openaimodel', 'displayname', 'cost_per_m_tokens', 'provider', 'supports_web_search')
                ->orderBy('provider')
                ->orderBy('displayname')
                ->get()
                ->groupBy('provider');
        } else {
            if ($hasLowTokens && $defaultModel) {
                $availableModels = \App\Models\AISettings::active()
                    ->whereNotNull('openaimodel')
                    ->whereNotNull('provider')
                    ->where('openaimodel', $defaultModel)
                    ->select('openaimodel', 'displayname', 'cost_per_m_tokens', 'provider', 'supports_web_search')
                    ->get()
                    ->groupBy('provider');
            } else {
                $userAllowedModels = $user->aiModels();
                $availableModels = \App\Models\AISettings::active()
                    ->whereNotNull('openaimodel')
                    ->whereNotNull('provider')
                    ->whereIn('openaimodel', $userAllowedModels)
                    ->select('openaimodel', 'displayname', 'cost_per_m_tokens', 'provider', 'supports_web_search')
                    ->orderBy('provider')
                    ->orderBy('displayname')
                    ->get()
                    ->groupBy('provider');
            }
        }

        return view('compare', compact(
            'groupedExperts',
            'experts',
            'availableModels',
            'defaultModel',
            'hasLowTokens',
            'hexCode'
        ));
    }

    public function multiModelChat(Request $request)
    {
        set_time_limit(300);

        Log::info('MultiModelChat request received', [
            'message' => $request->input('message'),
            'models' => $request->input('models'),
            'conversation_id' => $request->input('conversation_id'),
            'web_search' => $request->boolean('web_search'),
            'create_image' => $request->boolean('create_image'),
            'optimization_mode' => $request->input('optimization_mode', 'fixed'),
        ]);
        
        $request->validate([
            'message' => 'required|string',
            'models' => 'required|string',
            'conversation_id' => 'nullable|exists:multi_compare_conversations,id',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg,webp,gif|max:10240',
            'web_search' => 'sometimes|boolean',
            'create_image' => 'sometimes|boolean',
            'optimization_mode' => 'sometimes|string|in:fixed,smart_same,smart_all',
        ]);

        $message = $request->input('message');
        // ✅ DECODE FIRST - MOVE THIS TO THE TOP!
        if ($request->input('is_encoded') === '1') {
            $message = urldecode(base64_decode($message));
            Log::info('Message decoded', ['decoded_message' => $message]);
        }
        $modelsJson = $request->input('models');
        $selectedModels = json_decode($modelsJson, true);
        $conversationId = $request->input('conversation_id');
        $hexCode = $request->input('hex_code');
        $useWebSearch = $request->boolean('web_search');
        $createImage = $request->boolean('create_image');
        $optimizationMode = $request->input('optimization_mode', 'fixed');
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // ✅ TOKEN RESTRICTION ENFORCEMENT
        $siteSettings = app('siteSettings');
        $defaultModel = $siteSettings->default_model ?? null;
        $tokenLimit = (int) $siteSettings->token_left_conditional;
        $hasLowTokens = $user->tokens_left < $tokenLimit;

        // ✅ BACKEND VALIDATION: Prevent bypassing frontend restrictions through inspect element
        // This ensures server-side enforcement even if user modifies frontend code

        // Check if user has sufficient tokens for web search and attachments
        if ($user->tokens_left <= 0) {
            // Reject web search if tokens are insufficient
            if ($useWebSearch) {
                Log::warning('User attempted to use web search with insufficient tokens', [
                    'user_id' => $user->id,
                    'tokens_left' => $user->tokens_left,
                    'ip_address' => $request->ip()
                ]);
                
                return response()->json([
                    'error' => 'Insufficient tokens. Web search requires a minimum token balance. Please recharge your account to use this feature.'
                ], 403);
            }
            
            // Reject file uploads if tokens are insufficient
            if ($request->hasFile('files')) {
                Log::warning('User attempted to upload files with insufficient tokens', [
                    'user_id' => $user->id,
                    'tokens_left' => $user->tokens_left,
                    'file_count' => count($request->file('files')),
                    'ip_address' => $request->ip()
                ]);
                
                return response()->json([
                    'error' => 'Insufficient tokens. File attachments require a minimum token balance. Please recharge your account to use this feature.'
                ], 403);
            }
        }

        // Check if user has sufficient credits for image generation
        if ($user->credits_left == 0) {
            // Reject image generation if credits are insufficient
            if ($createImage) {
                Log::warning('User attempted to generate image with insufficient credits', [
                    'user_id' => $user->id,
                    'credits_left' => $user->credits_left,
                    'ip_address' => $request->ip()
                ]);
                
                return response()->json([
                    'error' => 'Insufficient credits. Image generation requires credits. Please recharge your account to use this feature.'
                ], 403);
            }
        }

        Log::info('Token and credit validation passed', [
            'user_id' => $user->id,
            'tokens_left' => $user->tokens_left,
            'credits_left' => $user->credits_left,
            'web_search' => $useWebSearch,
            'create_image' => $createImage,
            'has_files' => $request->hasFile('files')
        ]);

        // ✅ Check for export request (message is already decoded)
        $exportFormat = $this->detectFileExportRequest($message);

        if ($exportFormat && $hexCode) {
            Log::info('Export request detected', ['format' => $exportFormat, 'hex_code' => $hexCode]);
            
            // Find conversation by hex_code
            $exportConversation = MultiCompareConversation::where('hex_code', $hexCode)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$exportConversation) {
                Log::error('Conversation not found for export', ['hex_code' => $hexCode]);
                return response()->json(['error' => 'Conversation not found'], 404);
            }
            
            // ✅ SAVE USER MESSAGE FIRST
            $userMessage = MultiCompareMessage::create([
                'conversation_id' => $exportConversation->id,
                'role' => 'user',
                'content' => $message,
            ]);
            
            Log::info('Export user message saved', ['message_id' => $userMessage->id]);
            
            // ✅ PASS THE MESSAGE to the handler
            $exportResult = $this->handleFileExportRequest($exportFormat, $exportConversation->id, $selectedModels, $message);

            if ($exportResult['success']) {
                $exportResults = $exportResult['results']; // Multiple results, one per model
                
                // ✅ CREATE ASSISTANT RESPONSES FOR EACH MODEL
                $allResponsesData = [];
                $filesData = [];
                
                foreach ($exportResults as $modelId => $result) {
                    $assistantContent = "✅ **Export Complete**\n\n";
                    $assistantContent .= "I've successfully generated your **{$result['file_format']}** file.\n\n";
                    $assistantContent .= "**File Details:**\n";
                    $assistantContent .= "- Format: {$result['file_format']}\n";
                    $assistantContent .= "- Rows: {$result['rows']}\n";
                    $assistantContent .= "- Columns: {$result['columns']}\n\n";
                    $assistantContent .= "Click the download button below to save your file.";
                    
                    $allResponsesData[$modelId] = $assistantContent;
                    $filesData[] = $result;
                }
                
                // ✅ SAVE ASSISTANT MESSAGE WITH ALL MODEL EXPORTS
                $assistantMessage = MultiCompareMessage::create([
                    'conversation_id' => $exportConversation->id,
                    'role' => 'assistant',
                    'content' => 'Files exported successfully',
                    'all_responses' => array_merge($allResponsesData, ['files' => $filesData])
                ]);
                
                Log::info('Export assistant message saved', ['message_id' => $assistantMessage->id]);
                
                // ✅ UPDATE CONVERSATION TIMESTAMP
                $exportConversation->touch();
                
                // ✅ Return streaming response with SEPARATE files for each model
                return response()->stream(function () use ($exportResults, $selectedModels, $exportConversation, $allResponsesData) {
                    header('Content-Type: text/event-stream');
                    header('Cache-Control: no-cache');
                    header('Connection: keep-alive');
                    header('X-Accel-Buffering: no');

                    // ✅ Send init message
                    echo "data: " . json_encode([
                        'type' => 'init',
                        'models' => $selectedModels,
                        'conversation_id' => $exportConversation->id,
                        'hex_code' => $exportConversation->hex_code,
                    ]) . "\n\n";
                    ob_flush();
                    flush();

                    // ✅ Send INDIVIDUAL export responses for each model
                    foreach ($exportResults as $modelId => $result) {
                        $assistantContent = $allResponsesData[$modelId];
                        
                        // Send model start
                        echo "data: " . json_encode([
                            'type' => 'model_start',
                            'model' => $modelId,
                        ]) . "\n\n";
                        ob_flush();
                        flush();

                        // Send the assistant message content as chunks (for animation)
                        $words = explode(' ', $assistantContent);
                        $buffer = '';
                        foreach ($words as $word) {
                            $buffer .= $word . ' ';
                            echo "data: " . json_encode([
                                'type' => 'chunk',
                                'model' => $modelId,
                                'content' => $word . ' ',
                                'full_response' => trim($buffer)
                            ]) . "\n\n";
                            ob_flush();
                            flush();
                            usleep(5000); // 5ms delay for typing effect
                        }

                        // Send complete message
                        echo "data: " . json_encode([
                            'type' => 'complete',
                            'model' => $modelId,
                            'final_response' => $assistantContent
                        ]) . "\n\n";
                        ob_flush();
                        flush();

                        // ✅ Send file download info for THIS model only
                        Log::info('Sending file_generated event', [
                            'model' => $modelId,
                            'file_name' => $result['file_name'],
                            'download_url' => substr($result['download_url'], 0, 100) // Log partial URL
                        ]);

                        // ✅ Send file download info for THIS model only
                        Log::info('Sending file_generated event', [
                            'model' => $modelId,
                            'file_name' => $result['file_name'],
                            'download_url' => substr($result['download_url'], 0, 100) // Log partial URL
                        ]);

                        // ✅ Send file download info for THIS model only
                        echo "data: " . json_encode([
                            'type' => 'file_generated',
                            'model' => $modelId, // ✅ Specify which model this file is for
                            'download_url' => $result['download_url'],
                            'file_name' => $result['file_name'],
                            'file_format' => $result['file_format'],
                            'rows' => $result['rows'],
                            'columns' => $result['columns'],
                            'message' => "File generated successfully! {$result['rows']} rows × {$result['columns']} columns"
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                    }

                    // ✅ Send completion
                    echo "data: " . json_encode([
                        'type' => 'all_complete',
                        'conversation_id' => $exportConversation->id,
                        'hex_code' => $exportConversation->hex_code,
                    ]) . "\n\n";
                    
                    echo "data: [DONE]\n\n";
                    ob_flush();
                    flush();

                }, 200, [
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'Connection' => 'keep-alive',
                    'X-Accel-Buffering' => 'no',
                ]);
            } else {
                // ✅ If export failed, save error message
                $errorContent = "❌ Export failed: " . ($exportResult['error'] ?? 'Unknown error');
                
                MultiCompareMessage::create([
                    'conversation_id' => $exportConversation->id,
                    'role' => 'assistant',
                    'content' => $errorContent,
                    'all_responses' => [
                        'export' => $errorContent
                    ]
                ]);
                
                return response()->json([
                    'error' => $exportResult['error'] ?? 'Export failed'
                ], 500);
            }
        }

        if ($hasLowTokens && $defaultModel && $user->role === 'admin') {
            Log::warning('User has low tokens, enforcing default model restriction', [
                'user_id' => $user->id,
                'tokens_left' => $user->tokens_left,
                'default_model' => $defaultModel,
                'requested_models' => $selectedModels,
                'mode' => $optimizationMode
            ]);
            
            // ✅ FIXED: Keep mode unchanged, just use default model
            $selectedModels = [$defaultModel];
            
            Log::info('Models strictly enforced to default model (optimization will be skipped)', [
                'enforced_model' => $defaultModel,
                'mode_kept_as' => $optimizationMode
            ]);
        }

        Log::info('Multi-model chat request', [
            'message' => $message,
            'models' => $selectedModels,
            'conversation_id' => $conversationId,
            'web_search' => $useWebSearch,
            'create_image' => $createImage,
            'optimization_mode' => $optimizationMode,
            'token_restricted' => $hasLowTokens,
        ]);

        if (!is_array($selectedModels) || empty($selectedModels)) {
            return response()->json(['error' => 'Invalid models selection'], 400);
        }

        // ✅ OPTIMIZE MODELS FIRST if using smart modes (needed for both chat and image generation)
        $originalModels = $selectedModels;
        $optimizedModels = [];

        // Check if we need to optimize (smart modes and sufficient tokens)
        $needsOptimization = ($optimizationMode !== 'fixed' && !$hasLowTokens) ||
                           ($createImage && in_array('smart_all_auto', $selectedModels));

        if ($needsOptimization && in_array('smart_all_auto', $selectedModels)) {
            // ✅ FIXED: For image generation in smart mode, always use gemini-2.5-flash-image
            if ($createImage) {
                Log::info('Smart mode image generation - using fixed model', [
                    'mode' => $optimizationMode,
                    'original_models' => $selectedModels,
                    'fixed_model' => 'gemini-2.5-flash-image',
                    'reason' => 'Optimized for image generation (2 credits, supports editing)'
                ]);

                // Replace all placeholders with gemini-2.5-flash-image
                $selectedModels = ['gemini-2.5-flash-image'];
                $optimizedModels['smart_all_auto'] = 'gemini-2.5-flash-image';

                Log::info('Early optimization complete (image generation)', [
                    'original' => $originalModels,
                    'optimized' => $selectedModels
                ]);
            } else {
                // For text chat, use regular optimization
                $complexityScore = \App\Services\AI\QueryComplexityAnalyzer::analyze($message);

                Log::info('Optimizing models before processing', [
                    'mode' => $optimizationMode,
                    'complexity_score' => $complexityScore,
                    'original_models' => $selectedModels,
                    'for_image_generation' => $createImage
                ]);

                foreach ($selectedModels as $model) {
                    if ($model === 'smart_all_auto' || str_ends_with($model, '_smart_panel')) {
                        // Optimize placeholder models
                        if ($optimizationMode === 'smart_same') {
                            // For smart_same with placeholder, default to any provider
                            $suggestedModel = $this->getSuggestedModelFromAnyProvider($complexityScore, $model, $user, false);
                        } else {
                            // For smart_all, select best model across providers
                            $suggestedModel = $this->getSuggestedModelFromAnyProvider($complexityScore, $model, $user, false);
                        }
                        $optimizedModels[$model] = $suggestedModel;
                    } else {
                        // Real model, optionally optimize
                        if ($optimizationMode === 'smart_same') {
                            $suggestedModel = $this->getSuggestedModelFromComplexity($complexityScore, $model, $user, false);
                        } else if ($optimizationMode === 'smart_all') {
                            $suggestedModel = $this->getSuggestedModelFromAnyProvider($complexityScore, $model, $user, false);
                        } else {
                            $suggestedModel = $model;
                        }
                        $optimizedModels[$model] = $suggestedModel;
                    }

                    if ($model !== $optimizedModels[$model]) {
                        Log::info('Model optimized early', [
                            'from' => $model,
                            'to' => $optimizedModels[$model],
                            'mode' => $optimizationMode,
                            'complexity' => $complexityScore
                        ]);
                    }
                }

                $selectedModels = array_values(array_unique($optimizedModels));

                Log::info('Early optimization complete', [
                    'original' => $originalModels,
                    'optimized' => $selectedModels
                ]);
            }
        }

        // ✅ HANDLE IMAGE GENERATION (after optimization)
        if ($createImage) {
            return $this->handleMultiModelImageGeneration($request, $user, $selectedModels, $message);
        }

        $useWebSearch = $request->boolean('web_search');

        // ✅ Validate web search support if web search is requested
        // Skip validation for smart_all mode since it will optimize to a compatible model
        if ($useWebSearch && !in_array('smart_all_auto', $selectedModels)) {
            $modelsWithWebSearch = AISettings::whereIn('openaimodel', $selectedModels)
                ->where('supports_web_search', true)
                ->pluck('openaimodel')
                ->toArray();

            if (empty($modelsWithWebSearch)) {
                return response()->json([
                    'error' => 'None of the selected models support web search. Please select a model that supports web search or disable the web search option.'
                ], 400);
            }

            // ✅ Optional: Filter to only use models that support web search
            // $selectedModels = array_intersect($selectedModels, $modelsWithWebSearch);

            Log::info('Web search enabled with compatible models', [
                'models_with_web_search' => $modelsWithWebSearch
            ]);
        } elseif ($useWebSearch && in_array('smart_all_auto', $selectedModels)) {
            Log::info('Web search enabled with smart_all mode - will validate after optimization');
        }

        // ✅ CRITICAL FIX: Apply smart model optimization ONLY if NOT token-restricted AND not already optimized
        // Check if we already optimized (for image generation)
        $alreadyOptimized = !empty($optimizedModels);

        if (!$alreadyOptimized) {
            $originalModels = $selectedModels;
            $optimizedModels = [];
        }

        if ($optimizationMode !== 'fixed' && !$hasLowTokens && !$alreadyOptimized) {
            // ✅ Only run optimization when user has sufficient tokens and not already done
            $complexityScore = \App\Services\AI\QueryComplexityAnalyzer::analyze($message);

            Log::info('Applying model optimization', [
                'mode' => $optimizationMode,
                'complexity_score' => $complexityScore,
                'original_models' => $selectedModels
            ]);

            foreach ($selectedModels as $model) {
                if ($optimizationMode === 'smart_same') {
                    // Optimize within same provider
                    $suggestedModel = $this->getSuggestedModelFromComplexity($complexityScore, $model, $user, $useWebSearch);
                } else {
                    // Optimize across all providers
                    $suggestedModel = $this->getSuggestedModelFromAnyProvider($complexityScore, $model, $user, $useWebSearch);
                }

                $optimizedModels[$model] = $suggestedModel;

                if ($model !== $suggestedModel) {
                    Log::info('Model optimized', [
                        'from' => $model,
                        'to' => $suggestedModel,
                        'mode' => $optimizationMode,
                        'complexity' => $complexityScore
                    ]);
                }
            }

            // Replace models with optimized versions
            $selectedModels = array_values(array_unique($optimizedModels));

            Log::info('Optimization complete', [
                'original_count' => count($originalModels),
                'optimized_count' => count($selectedModels),
                'optimized_models' => $selectedModels
            ]);

            // ✅ Validate web search support after optimization
            if ($useWebSearch) {
                $modelsWithWebSearch = AISettings::whereIn('openaimodel', $selectedModels)
                    ->where('supports_web_search', true)
                    ->pluck('openaimodel')
                    ->toArray();

                if (empty($modelsWithWebSearch)) {
                    Log::warning('Optimized model does not support web search', [
                        'optimized_models' => $selectedModels,
                        'web_search_requested' => true
                    ]);

                    return response()->json([
                        'error' => 'The optimized model does not support web search. Please try again with a different optimization mode or disable web search.'
                    ], 400);
                }

                Log::info('Web search validated after optimization', [
                    'models_with_web_search' => $modelsWithWebSearch
                ]);
            }
        } elseif ($hasLowTokens && $optimizationMode !== 'fixed') {
            // ✅ Log that optimization was skipped due to low tokens
            Log::info('Skipping optimization due to low tokens - using default model', [
                'tokens_left' => $user->tokens_left,
                'default_model' => $defaultModel,
                'mode' => $optimizationMode,
                'note' => 'Mode kept unchanged for proper panel rendering'
            ]);

            // ✅ IMPORTANT: Set optimizedModels for the init message
            // This tells the frontend that default model is being used
            $optimizedModels = [$defaultModel => $defaultModel];
        }

        // ✅ ENHANCED FILE UPLOAD HANDLING
        // ✅ ENHANCED MULTIPLE FILE UPLOAD HANDLING
        $fileContentMessages = [];
        $uploadedFiles = [];
        $azureFileUrls = [];
        $fileErrors = []; // Track errors for individual files

        if ($request->hasFile('files')) {
            $files = $request->file('files');
            
            // Ensure it's an array
            if (!is_array($files)) {
                $files = [$files];
            }
            
            Log::info('Processing multiple files', ['count' => count($files)]);

            // ✅ Limit number of files
            if (count($files) > 10) {
                return response()->json([
                    'error' => 'Maximum 10 files allowed per message'
                ], 400);
            }

            // ✅ NEW: Validate total file size (max 50MB combined)
            $totalSize = 0;
            $maxTotalSize = 50 * 1024 * 1024; // 50MB
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $totalSize += $file->getSize();
                }
            }
            
            if ($totalSize > $maxTotalSize) {
                return response()->json([
                    'error' => 'Total file size exceeds maximum of 50MB. Current total: ' . round($totalSize / 1024 / 1024, 2) . 'MB'
                ], 400);
            }

            // Process each file individually (continue on errors)
            foreach ($files as $index => $file) {
                if (!$file->isValid()) {
                    $fileErrors[] = [
                        'file' => $file->getClientOriginalName() ?? "File #{$index}",
                        'error' => 'File is not valid'
                    ];
                    continue;
                }

                $extension = strtolower($file->getClientOriginalExtension());
                $originalFileName = $file->getClientOriginalName();
                $tempPath = null;
                $fullPath = null;

                try {
                    // ✅ Security validation
                    $securityCheck = $this->validateFileSecurity($file, $extension, $originalFileName);
                    if (!$securityCheck['valid']) {
                        $fileErrors[] = [
                            'file' => $originalFileName,
                            'error' => $securityCheck['error']
                        ];
                        continue; // Skip this file but continue with others
                    }

                    $tempPath = $file->storeAs('temp', uniqid() . '.' . $extension);
                    $fullPath = storage_path("app/{$tempPath}");

                    // Upload to Azure first
                    $blobClient = BlobRestProxy::createBlobService(config('filesystems.disks.azure.connection_string'));
                    $containerName = config('filesystems.disks.azure.container');
                    $azureFileName = 'chattermate-multi-compare/' . uniqid() . '_' . $originalFileName;
                    $fileContent = file_get_contents($fullPath);
                    $blobClient->createBlockBlob($containerName, $azureFileName, $fileContent, new CreateBlockBlobOptions());

                    $baseUrl = rtrim(config('filesystems.disks.azure.url'), '/');
                    // ✅ FIX: URL-encode the file path to handle spaces and special characters
                    $encodedPath = implode('/', array_map('rawurlencode', explode('/', $azureFileName)));
                    $azureFileUrl = "{$baseUrl}/{$containerName}/{$encodedPath}";
                    $azureFileUrls[] = $azureFileUrl;

                    $uploadedFiles[] = [
                        'file_path' => $azureFileName,
                        'file_name' => $originalFileName,
                        'file_type' => $extension,
                        'azure_url' => $azureFileUrl,
                    ];

                    // Process file based on type
                    if (in_array($extension, ['pdf', 'doc', 'docx'])) {
                        $text = '';
                        
                        try {
                            if ($extension === 'pdf') {
                                $text = $this->extractTextFromPDF($fullPath);
                            } else {
                                $text = $this->extractTextFromDOCX($fullPath);
                            }

                            if (empty(trim($text))) {
                                Log::warning('PDF extraction returned empty text', [
                                    'file' => $originalFileName,
                                    'extension' => $extension,
                                    'file_size' => filesize($fullPath)
                                ]);
                                $fileContentMessages[] = [
                                    'role' => 'system',
                                    'content' => "Content from {$originalFileName}:\n\n[File uploaded but no text could be extracted. This may be an image-based PDF. Please inform the user that the PDF content could not be read.]",
                                ];
                            } else {
                                Log::info('Document text extracted successfully', [
                                    'file' => $originalFileName,
                                    'text_length' => strlen($text),
                                    'preview' => substr($text, 0, 200) . (strlen($text) > 200 ? '...' : '')
                                ]);
                                $fileContentMessages[] = [
                                    'role' => 'system',
                                    'content' => "Content from {$originalFileName}:\n\n{$text}",
                                ];
                            }
                        } catch (\Exception $extractEx) {
                            Log::error('Text extraction error', [
                                'file' => $originalFileName,
                                'error' => $extractEx->getMessage()
                            ]);
                            $fileContentMessages[] = [
                                'role' => 'system',
                                'content' => "Content from {$originalFileName}:\n\n[Error extracting text: " . $extractEx->getMessage() . "]",
                            ];
                        }

                    } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                        // Store image URL for later inclusion in user message
                        $fileContentMessages[] = [
                            'type' => 'image',
                            'url' => $azureFileUrl,
                            'name' => $originalFileName,
                        ];
                    }

                } catch (\Exception $e) {
                    Log::error('File processing error', [
                        'file' => $originalFileName,
                        'error' => $e->getMessage()
                    ]);
                    $fileErrors[] = [
                        'file' => $originalFileName,
                        'error' => 'Failed to process file: ' . $e->getMessage()
                    ];
                } finally {
                    // Clean up temp file if it was created
                    if ($tempPath && Storage::exists($tempPath)) {
                        Storage::delete($tempPath);
                    }
                }
            }
            
            // ✅ NEW: If all files failed, return error
            if (count($uploadedFiles) === 0 && count($files) > 0) {
                $errorMessages = array_map(function($err) {
                    return $err['file'] . ': ' . $err['error'];
                }, $fileErrors);
                
                return response()->json([
                    'error' => 'All files failed to upload. ' . implode(' | ', $errorMessages)
                ], 400);
            }
            
            // ✅ NEW: If some files failed, log warning but continue
            if (count($fileErrors) > 0) {
                Log::warning('Some files failed to process', [
                    'failed_count' => count($fileErrors),
                    'success_count' => count($uploadedFiles),
                    'errors' => $fileErrors
                ]);
            }
            
            Log::info('Processed files', [
                'total' => count($uploadedFiles),
                'documents' => count(array_filter($fileContentMessages, fn($m) => isset($m['role']) && $m['role'] === 'system')),
                'images' => count(array_filter($fileContentMessages, fn($m) => isset($m['type']) && $m['type'] === 'image')),
                'errors' => count($fileErrors)
            ]);
        }

        // Get or create conversation
        $conversation = null;
        if ($hexCode) {
            $conversation = MultiCompareConversation::where('hex_code', $hexCode)
                ->where('user_id', $user->id)
                ->first();
        }

        // Around line 67-77, update this section:
        if (!$conversation) {
            $conversation = MultiCompareConversation::create([
                'user_id' => $user->id,
                'title' => $this->generateConversationTitle($message),
                'selected_models' => $selectedModels,
                'optimization_mode' => $request->input('optimization_mode', 'fixed')
            ]);
        } else {
            $conversation->update([
                'selected_models' => $selectedModels,
                'optimization_mode' => $request->input('optimization_mode', 'fixed')
            ]);
        }

        // Get conversation history
        $conversationHistory = [];
        $messages = $conversation->messages()->with('attachments')->orderBy('created_at')->get();
        
        foreach ($messages as $msg) {
            if ($msg->role === 'user') {
                // ✅ Check if this message has attachments
                if ($msg->attachments && $msg->attachments->count() > 0) {
                    // Re-add file content from attachments
                    foreach ($msg->attachments as $attachment) {
                        $baseUrl = rtrim(config('filesystems.disks.azure.url'), '/');
                        $containerName = config('filesystems.disks.azure.container');
                        // ✅ FIX: URL-encode the file path to handle spaces and special characters
                        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $attachment->file_path)));
                        $azureUrl = "{$baseUrl}/{$containerName}/{$encodedPath}";
                        
                        // Get file extension
                        $extension = strtolower($attachment->file_type);
                        
                        // For documents (PDF, DOCX), we need to re-extract text
                        if (in_array($extension, ['pdf', 'doc', 'docx'])) {
                            try {
                                // Download file from Azure with proper URL encoding
                                $ch = curl_init($azureUrl);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                                $fileContent = curl_exec($ch);
                                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                $curlError = curl_error($ch);
                                curl_close($ch);
                                
                                if ($fileContent === false || $httpCode !== 200) {
                                    throw new \Exception("Failed to download file from Azure: HTTP {$httpCode} - {$curlError}");
                                }
                                
                                $tempPath = storage_path("app/temp/" . uniqid() . '.' . $extension);
                                file_put_contents($tempPath, $fileContent);
                                
                                // Extract text
                                $text = '';
                                if ($extension === 'pdf') {
                                    $text = $this->extractTextFromPDF($tempPath);
                                } else {
                                    $text = $this->extractTextFromDOCX($tempPath);
                                }
                                
                                // Add as system message
                                $conversationHistory[] = [
                                    'role' => 'system',
                                    'content' => "Content from {$attachment->file_name}:\n\n{$text}"
                                ];
                                
                                // Clean up temp file
                                unlink($tempPath);
                                
                            } catch (\Exception $e) {
                                Log::error('Error re-extracting file content for history', [
                                    'file' => $attachment->file_name,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                        // For images, we'll handle them in the user message content below
                    }
                }
                
                // Add user message
                // Check if message has image attachments
                $imageAttachments = [];
                if ($msg->attachments && $msg->attachments->count() > 0) {
                    foreach ($msg->attachments as $attachment) {
                        $extension = strtolower($attachment->file_type);
                        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                            $baseUrl = rtrim(config('filesystems.disks.azure.url'), '/');
                            $containerName = config('filesystems.disks.azure.container');
                            // ✅ FIX: URL-encode the file path to handle spaces and special characters
                            $encodedPath = implode('/', array_map('rawurlencode', explode('/', $attachment->file_path)));
                            $azureUrl = "{$baseUrl}/{$containerName}/{$encodedPath}";
                            $imageAttachments[] = $azureUrl;
                        }
                    }
                }
                
                // Build user message content
                if (!empty($imageAttachments)) {
                    // Multimodal message with text + images
                    $userContent = [];
                    $userContent[] = ['type' => 'text', 'text' => $msg->content];
                    foreach ($imageAttachments as $imageUrl) {
                        $userContent[] = [
                            'type' => 'image_url',
                            'image_url' => ['url' => $imageUrl]
                        ];
                    }
                    $conversationHistory[] = ['role' => 'user', 'content' => $userContent];
                } else {
                    // Text-only message
                    $conversationHistory[] = ['role' => 'user', 'content' => $msg->content];
                }
                
            } elseif ($msg->role === 'assistant' && $msg->all_responses) {
                foreach ($msg->all_responses as $model => $response) {
                    $conversationHistory[] = ['role' => 'assistant', 'content' => $response];
                    break;
                }
            }
        }

        // Prepare base messages
        $baseMessages = [];
        
        $systemMessage = "You are a helpful AI assistant. Respond clearly and concisely.";
        
        // Add math and visualization instructions
        $needsMath = preg_match('/\b(math|equation|formula|calculate|integral|integrate|integration|derivative|differentiate|differentiation|sum|algebra|geometry|trigonometry|calculus|solve|sqrt|fraction|logarithm|log|ln|sin|cos|tan|exp|factorial|permutation|combination|matrix|vector|polar|cartesian|limit|limits|simplify|expand|factor|polynomial|quadratic|cubic)\b/i', $message);
        $needsVisualization = preg_match('/\b(plot|graph|chart|visualize|show.*graph|draw|diagram|display|illustrate|represent|render|sketch|map|figure|outline|exhibit|demonstrate|view|table)\b/i', $message);

        if ($needsMath) {
            $systemMessage .= "\n\n**IMPORTANT: For mathematical expressions, use LaTeX notation:**
            - Inline math: \$x^2 + y^2 = z^2\$
            - Display math: \$\$\\int_0^\\infty e^{-x^2} dx = \\frac{\\sqrt{\\pi}}{2}\$\$";
        }

        if ($needsVisualization) {
            $systemMessage .= "\n\n**IMPORTANT: For charts/graphs, use Chart.js JSON format wrapped in ```chart blocks.**
            You can create MULTIPLE charts - each in its own chart block.
            
            Example:
                ```chart
                    {
                        \"type\": \"line\",
                        \"data\": {
                            \"labels\": [\"Jan\", \"Feb\", \"Mar\"],
                            \"datasets\": [{
                                \"label\": \"Sales\",
                                \"data\": [10, 20, 30],
                                \"borderColor\": \"rgb(75, 192, 192)\"
                            }]
                        },
                        \"options\": {
                            \"responsive\": true
                        }
                    }
                ```
            
            Supported types: line, bar, pie, doughnut, scatter, radar.";
        }

        if ($useWebSearch) {
            $systemMessage .= "\n\nYou have access to web search capabilities. Use them when current information is needed.";
        }

        $baseMessages[] = ['role' => 'system', 'content' => $systemMessage];

        foreach ($conversationHistory as $historyMessage) {
            $baseMessages[] = $historyMessage;
        }

        // ✅ STEP 3: Handle multiple files - separate documents from images
        // ✅ Handle multiple files - separate documents from images
        $documentMessages = array_filter($fileContentMessages, function($msg) {
            return isset($msg['role']) && $msg['role'] === 'system';
        });

        $imageAttachments = array_filter($fileContentMessages, function($msg) {
            return isset($msg['type']) && $msg['type'] === 'image';
        });

        // ✅ NEW: Validate image count against model limits
        $imageCount = count($imageAttachments);
        if ($imageCount > 0) {
            $imageValidation = $this->validateImageCountForModels($imageCount, $selectedModels);
            if (!$imageValidation['valid']) {
                return response()->json([
                    'error' => $imageValidation['error']
                ], 400);
            }
        }

        // Add document content as system messages
        Log::info('Adding document messages to baseMessages', [
            'document_count' => count($documentMessages),
            'document_previews' => array_map(function($doc) {
                return substr($doc['content'], 0, 100) . '...';
            }, $documentMessages)
        ]);
        
        foreach ($documentMessages as $docMsg) {
            $baseMessages[] = $docMsg;
        }

        // Build user message with images (if any)
        if (count($imageAttachments) > 0) {
            // Multimodal message with text + images
            $userMessageContent = [];
            
            // ✅ FIX: Include document references in user message text when both documents and images exist
            $userText = $message;
            if (count($documentMessages) > 0) {
                $docNames = [];
                foreach ($documentMessages as $docMsg) {
                    // Extract filename from system message content
                    if (preg_match('/Content from ([^:]+):/', $docMsg['content'], $matches)) {
                        $docNames[] = $matches[1];
                    } else {
                        $docNames[] = 'uploaded document';
                    }
                }
                
                if (count($docNames) > 0) {
                    $docList = count($docNames) === 1 ? $docNames[0] : implode(', ', array_slice($docNames, 0, -1)) . ' and ' . end($docNames);
                    $userText .= "\n\n[IMPORTANT: I have also uploaded " . (count($docNames) === 1 ? 'a document' : count($docNames) . ' documents') . " (" . $docList . "). The content from " . (count($docNames) === 1 ? 'this document' : 'these documents') . " has been provided in the system context above. Please reference and use this document content when answering my questions.]";
                }
            }
            
            // Add text first
            $userMessageContent[] = ['type' => 'text', 'text' => $userText];
            
            // Add all images
            foreach ($imageAttachments as $image) {
                $userMessageContent[] = [
                    'type' => 'image_url',
                    'image_url' => ['url' => $image['url']]
                ];
            }
            
            $baseMessages[] = [
                'role' => 'user',
                'content' => $userMessageContent
            ];
            
            Log::info('Created multimodal user message', [
                'text_length' => strlen($userText),
                'images' => count($imageAttachments),
                'documents' => count($documentMessages),
                'models' => $selectedModels
            ]);
        } else {
            // Text-only message (no images)
            $baseMessages[] = [
                'role' => 'user',
                'content' => $message
            ];
        }

        Log::info('Base messages prepared', [
            'message_count' => count($baseMessages),
            'message_types' => array_map(function($msg) {
                return $msg['role'] ?? 'unknown';
            }, $baseMessages),
            'has_documents' => count($documentMessages) > 0,
            'has_images' => count($imageAttachments) > 0,
            'document_count' => count($documentMessages),
            'image_count' => count($imageAttachments)
        ]);

            // Return streaming response
        return response()->stream(function () use ($selectedModels, $baseMessages, $useWebSearch, $message, $conversation, $uploadedFiles, $fileContentMessages, $optimizedModels, $originalModels) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            $responses = [];
            $allResponses = [];

            echo "data: " . json_encode([
                'type' => 'init',
                'models' => $selectedModels,
                'conversation_id' => $conversation->id,
                'hex_code' => $conversation->hex_code,  // ✅ ADD THIS
                'message' => 'Starting comparison...',
                'optimized_models' => $optimizedModels ?? null, // ✅ NEW: Send optimization info
                'original_models' => $originalModels ?? null, // ✅ NEW
            ]) . "\n\n";
            ob_flush();
            flush();

            // ✅ PROCESS ALL MODELS SIMULTANEOUSLY
            $this->processModelsSimultaneously($selectedModels, $baseMessages, $useWebSearch, $responses, $allResponses);

            try {
                $this->saveMultiCompareConversation($conversation, $message, $allResponses, $uploadedFiles, $fileContentMessages);
            } catch (\Exception $e) {
                Log::error('Error saving conversation: ' . $e->getMessage());
            }

            // ✅ Send updated token status
            /** @var \App\Models\User $updatedUser */
            $updatedUser = Auth::user()->fresh(); // Refresh user data
            $updatedTokens = $updatedUser->tokens_left;

            // Get token limit from site settings (singleton)
            $tokenLimit = (int) app('siteSettings')->token_left_conditional;
            $nowHasLowTokens = $updatedTokens < $tokenLimit;

            echo "data: " . json_encode([
                'type' => 'all_complete',
                'responses' => $responses,
                'conversation_id' => $conversation->id,
                'hex_code' => $conversation->hex_code,  // ✅ ADD THIS
                'tokens_left' => $updatedTokens,  // ✅ NEW
                'has_low_tokens' => $nowHasLowTokens  // ✅ NEW
            ]) . "\n\n";
            
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();

            try {
                if (Auth::check()) {
                    // ✅ Use actual token usage from API responses
                    foreach ($selectedModels as $modelName) {
                        // Get actual tokens from streaming response
                        $actualTokens = self::$simultaneousTokenUsage[$modelName] ?? 0;
                        
                        // ✅ Fallback to approximation ONLY if actual tokens not captured
                        if ($actualTokens === 0) {
                            Log::warning("Token usage not captured from API, using approximation", [
                                'model' => $modelName
                            ]);
                            
                            $baseInputTokens = 0;
                            foreach ($baseMessages as $msg) {
                                $encodedMessage = json_encode($msg);
                                $baseInputTokens += approximateTokenCount($encodedMessage);
                            }
                            
                            $modelResponse = $responses[$modelName] ?? '';
                            $responseTokens = approximateTokenCount($modelResponse);
                            $actualTokens = $baseInputTokens + $responseTokens;
                        }
                        
                        // Deduct with model-specific multiplier
                        deductUserTokensAndCredits($actualTokens, 0, $modelName);
                        
                        // Enhanced logging
                        Log::info("Token deduction for model", [
                            'model' => $modelName,
                            'actual_tokens_from_api' => $actualTokens,
                            'multiplier' => getModelMultiplier($modelName),
                            'final_tokens_after_multiplier' => ceil($actualTokens * getModelMultiplier($modelName))
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error deducting tokens: ' . $e->getMessage());
            }

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    // ✅ NEW METHOD: Process all models simultaneously using curl_multi
    // ✅ UPDATED METHOD: Process all models simultaneously using curl_multi
    private function processModelsSimultaneously($selectedModels, $baseMessages, $useWebSearch, &$responses, &$allResponses)
    {
        // Clear static buffers before starting
        self::$simultaneousBuffers = [];
        self::$simultaneousResponses = [];
        self::$simultaneousTokenUsage = []; // ✅ NEW: Clear token usage tracking
        
        // Initialize responses for all models
        foreach ($selectedModels as $model) {
            $responses[$model] = '';
            self::$simultaneousResponses[$model] = '';
            
            echo "data: " . json_encode([
                'type' => 'model_start',
                'model' => $model,
                'message' => 'Processing...'
            ]) . "\n\n";
            ob_flush();
            flush();
        }

        // Create curl handles for all models
        $curlHandles = [];
        $modelMapping = []; // Maps curl handle to model info
        
        $mh = curl_multi_init();

        foreach ($selectedModels as $model) {
            $ch = $this->createCurlHandleForModel($model, $baseMessages, $useWebSearch);
            
            if ($ch) {
                curl_multi_add_handle($mh, $ch);
                $curlHandles[(int)$ch] = $ch;
                $modelMapping[(int)$ch] = $model;
            }
        }

        // Execute all handles simultaneously
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            
            // Process any available data
            while ($info = curl_multi_info_read($mh)) {
                $handle = $info['handle'];
                $handleId = (int)$handle;
                
                if (isset($modelMapping[$handleId])) {
                    $model = $modelMapping[$handleId];
                    
                    if ($info['result'] === CURLE_OK) {
                        // ✅ FIX: Copy final response from static array to the passed arrays
                        $responses[$model] = self::$simultaneousResponses[$model] ?? '';
                        $allResponses[$model] = $responses[$model];
                        
                        Log::info("Model {$model} completed successfully", [
                            'response_length' => strlen($responses[$model])
                        ]);
                        
                        echo "data: " . json_encode([
                            'type' => 'complete',
                            'model' => $model,
                            'final_response' => $responses[$model]
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                    } else {
                        $error = curl_error($handle);
                        Log::error("Error processing model {$model}: {$error}");
                        
                        $errorMessage = 'Error: ' . $error;
                        $responses[$model] = $errorMessage;
                        $allResponses[$model] = $errorMessage;
                        
                        echo "data: " . json_encode([
                            'type' => 'error',
                            'model' => $model,
                            'error' => $errorMessage
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                    }
                }
                
                curl_multi_remove_handle($mh, $handle);
                curl_close($handle);
            }
            
            // Small delay to prevent CPU spinning
            if ($running > 0) {
                curl_multi_select($mh, 0.1);
            }
        } while ($running > 0);

        curl_multi_close($mh);
        
        // ✅ FIX: Final sync - ensure all responses are captured
        foreach ($selectedModels as $model) {
            if (!isset($allResponses[$model]) && isset(self::$simultaneousResponses[$model])) {
                $responses[$model] = self::$simultaneousResponses[$model];
                $allResponses[$model] = self::$simultaneousResponses[$model];
            }
        }
        
        Log::info('All models processed', [
            'models_count' => count($allResponses),
            'models' => array_keys($allResponses),
            'response_lengths' => array_map('strlen', $allResponses)
        ]);
    }

    // ✅ NEW METHOD: Create curl handle for a specific model
    private function createCurlHandleForModel($model, $messages, $useWebSearch)
    {
        try {
            if ($this->isClaudeModel($model)) {
                return $this->createClaudeCurlHandle($model, $messages, $useWebSearch);
            } elseif ($this->isGeminiModel($model)) {
                return $this->createGeminiCurlHandle($model, $messages, $useWebSearch);
            } elseif ($this->isGrokModel($model)) {
                return $this->createGrokCurlHandle($model, $messages, $useWebSearch);
            } else {
                return $this->createOpenAICurlHandle($model, $messages, $useWebSearch);
            }
        } catch (\Exception $e) {
            Log::error("Error creating curl handle for {$model}: " . $e->getMessage());
            return null;
        }
    }

    // ✅ NEW METHOD: Create OpenAI curl handle
    // ✅ UPDATED METHOD: Create OpenAI curl handle using Responses API
    private function createOpenAICurlHandle($model, $messages, $useWebSearch)
    {
        $apiKey = config('services.openai.api_key');
        
        // ✅ NEW: Convert messages to Responses API format
        $input = $this->convertMessagesToResponsesFormat($messages);
        
        // ✅ NEW: Build payload for Responses API
        $payload = [
            'model' => $model,
            'input' => $input['input'],
            'stream' => true,
        ];
        
        // ✅ Add system instructions if present (Responses API uses separate 'instructions' field)
        if (!empty($input['instructions'])) {
            $payload['instructions'] = $input['instructions'];
        }
        
        // ✅ Add web search tool if requested
        if ($useWebSearch) {
            $payload['tools'] = [
                [
                    'type' => 'web_search'
                ]
            ];
            
            Log::info('OpenAI web search enabled via Responses API', [
                'model' => $model
            ]);
        }
        
        Log::debug('OpenAI Responses API Payload', [
            'model' => $model,
            'input_type' => gettype($input['input']),
            'has_instructions' => !empty($input['instructions']),
            'tools' => $payload['tools'] ?? null
        ]);

        // ✅ UPDATED: Change endpoint from /v1/chat/completions to /v1/responses
        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 300, // 5 minute timeout for long responses
            CURLOPT_CONNECTTIMEOUT => 30, // 30 second connection timeout
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($model, &$responses) {
                return $this->handleStreamChunkSimultaneous($model, $data, 'openai');
            }
        ]);

        return $ch;
    }

    // ✅ NEW METHOD: Convert messages to Responses API format
    /**
     * Convert chat messages array to Responses API format
     * 
     * Responses API uses 'input' which can be:
     * - A simple string for single messages
     * - An array of items for complex multi-turn conversations with images
     * 
     * System messages are moved to separate 'instructions' field
     */
    private function convertMessagesToResponsesFormat($messages)
    {
        $instructions = [];
        $inputItems = [];
        
        foreach ($messages as $msg) {
            // Extract system message as instructions
            if ($msg['role'] === 'system') {
                $instructions[] = $msg['content'];
                continue;
            }
            
            // Handle multimodal content (images + text)
            if (isset($msg['content']) && is_array($msg['content'])) {
                $parts = [];
                
                foreach ($msg['content'] as $content) {
                    if ($content['type'] === 'text') {
                        $parts[] = [
                            'type' => $msg['role'] === 'user' ? 'input_text' : 'output_text',
                            'text' => $content['text']
                        ];
                    } elseif ($content['type'] === 'image_url') {
                        $parts[] = [
                            'type' => 'input_image',
                            'image_url' => $content['image_url']['url']
                        ];
                    }
                }
                
                $inputItems[] = [
                    'role' => $msg['role'],
                    'content' => $parts
                ];
            } else {
                // Simple text message
                $inputItems[] = [
                    'role' => $msg['role'],
                    'content' => [
                        [
                            'type' => $msg['role'] === 'user' ? 'input_text' : 'output_text',
                            'text' => $msg['content']
                        ]
                    ]
                ];
            }
        }
        
        // ✅ For simple single-message queries, we can use string input
        // For multi-turn or multimodal, use array format
        $input = null;
        if (count($inputItems) === 1 && 
            count($inputItems[0]['content']) === 1 && 
            $inputItems[0]['content'][0]['type'] === 'input_text') {
            // Simple string input
            $input = $inputItems[0]['content'][0]['text'];
        } else {
            // Complex array input
            $input = $inputItems;
        }
        
        return [
            'input' => $input,
        'instructions' => !empty($instructions) ? implode("\n\n", $instructions) : null
        ];
    }

    // ✅ NEW METHOD: Create Claude curl handle
    private function createClaudeCurlHandle($model, $messages, $useWebSearch)
    {
        $claudeData = $this->convertMessagesToClaudeFormat($messages);
        
        $payload = [
            'model' => $model,
            'messages' => $claudeData['messages'],
            'max_tokens' => 4096,
            'stream' => true,
        ];

        if ($claudeData['system']) {
            $payload['system'] = $claudeData['system'];
        }

        if ($useWebSearch) {
            $payload['tools'] = [
                [
                    'type' => 'web_search_20250305',
                    'name' => 'web_search',
                    'max_uses' => 5
                ]
            ];
        }

        $apiKey = config('services.anthropic.api_key');
        
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 300, // 5 minute timeout for long responses
            CURLOPT_CONNECTTIMEOUT => 30, // 30 second connection timeout
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($model, &$responses) {
                return $this->handleStreamChunkSimultaneous($model, $data, 'claude');
            }
        ]);

        return $ch;
    }

    // ✅ NEW METHOD: Create Gemini curl handle
    private function createGeminiCurlHandle($model, $messages, $useWebSearch)
    {
        $geminiMessages = $this->convertMessagesToGeminiFormat($messages);
        $apiKey = config('services.gemini.api_key');
        
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?alt=sse&key={$apiKey}";
        
        $payload = [
            'contents' => $geminiMessages['contents'],
            'generationConfig' => [
                'temperature' => 1.0,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
            ],
        ];
        
        if ($geminiMessages['systemInstruction']) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $geminiMessages['systemInstruction']]
                ]
            ];
        }
        
        if ($useWebSearch) {
            if (str_contains($model, '2.') || str_contains($model, 'flash-002')) {
                $payload['tools'] = [['google_search' => (object)[]]];
            } else {
                $payload['tools'] = [[
                    'googleSearchRetrieval' => [
                        'dynamicRetrievalConfig' => [
                            'mode' => 'MODE_DYNAMIC',
                            'dynamicThreshold' => 0.7
                        ]
                    ]
                ]];
            }
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 300, // 5 minute timeout for long responses
            CURLOPT_CONNECTTIMEOUT => 30, // 30 second connection timeout
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($model, &$responses) {
                return $this->handleStreamChunkSimultaneous($model, $data, 'gemini');
            }
        ]);

        return $ch;
    }

    // ✅ NEW METHOD: Create Grok curl handle
    private function createGrokCurlHandle($model, $messages, $useWebSearch)
    {
        $apiKey = config('services.xai.api_key');

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
            'temperature' => 0.7,
            'max_tokens' => 4096,
            'stream_options' => [  // ✅ ADD THIS
                'include_usage' => true
            ]
        ];

        if ($useWebSearch) {
            $currentDate = date('Y-m-d');
            $lastMessage = end($messages);
            if ($lastMessage && $lastMessage['role'] === 'user') {
                $originalContent = $lastMessage['content'];
                $messages[count($messages) - 1]['content'] =
                    "Current date: {$currentDate}. " .
                    "Please search for and use only the most recent, up-to-date information when answering: " .
                    $originalContent;
            }
            $payload['messages'] = $messages;
        }

        $ch = curl_init('https://api.x.ai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 300, // 5 minute timeout for long responses
            CURLOPT_CONNECTTIMEOUT => 30, // 30 second connection timeout
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($model, &$responses) {
                return $this->handleStreamChunkSimultaneous($model, $data, 'grok');
            }
        ]);

        return $ch;
    }

    // ✅ NEW METHOD: Handle stream chunks for simultaneous processing
    private static $simultaneousBuffers = [];
    private static $simultaneousResponses = [];
    private static $simultaneousTokenUsage = []; // ✅ NEW: Track actual token usage


    // ✅ UPDATED METHOD: Handle stream chunks for OpenAI Responses API
    /**
     * Handle streaming response chunks from different providers
     * 
     * UPDATED: Added support for OpenAI Responses API event structure
     * The Responses API uses different event types and structure compared to Chat Completions
     */
    private function handleStreamChunkSimultaneous($model, $data, $provider)
    {
        if (!isset(self::$simultaneousBuffers[$model])) {
            self::$simultaneousBuffers[$model] = '';
        }

        self::$simultaneousBuffers[$model] .= $data;
        $lines = explode("\n", self::$simultaneousBuffers[$model]);
        
        // Keep last incomplete line in buffer
        self::$simultaneousBuffers[$model] = array_pop($lines);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line === 'data: [DONE]') {
                continue;
            }

            if (strpos($line, 'data: ') === 0) {
                $json = substr($line, 6);
                
                try {
                    $decoded = json_decode($json, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        continue;
                    }
                    
                    $content = '';
                    
                    if ($provider === 'claude') {
                        if (isset($decoded['type']) && $decoded['type'] === 'content_block_delta') {
                            $content = $decoded['delta']['text'] ?? '';
                        }
                        
                        // Capture Claude token usage
                        if (isset($decoded['type']) && $decoded['type'] === 'message_delta') {
                            if (isset($decoded['usage']['input_tokens'], $decoded['usage']['output_tokens'])) {
                                $totalTokens = $decoded['usage']['input_tokens'] + $decoded['usage']['output_tokens'];
                                self::$simultaneousTokenUsage[$model] = $totalTokens;
                                
                                Log::info("Claude token usage captured", [
                                    'model' => $model,
                                    'input_tokens' => $decoded['usage']['input_tokens'],
                                    'output_tokens' => $decoded['usage']['output_tokens'],
                                    'total_tokens' => $totalTokens
                                ]);
                            }
                        }
                        
                    } elseif ($provider === 'gemini') {
                        if (isset($decoded['candidates'][0]['content']['parts'])) {
                            foreach ($decoded['candidates'][0]['content']['parts'] as $part) {
                                if (isset($part['text'])) {
                                    $content .= $part['text'];
                                }
                            }
                        }
                        
                        // Capture Gemini token usage
                        if (isset($decoded['usageMetadata']['totalTokenCount'])) {
                            self::$simultaneousTokenUsage[$model] = $decoded['usageMetadata']['totalTokenCount'];
                            
                            Log::info("Gemini token usage captured", [
                                'model' => $model,
                                'prompt_tokens' => $decoded['usageMetadata']['promptTokenCount'] ?? 0,
                                'candidates_tokens' => $decoded['usageMetadata']['candidatesTokenCount'] ?? 0,
                                'total_tokens' => $decoded['usageMetadata']['totalTokenCount']
                            ]);
                        }
                        
                    } elseif ($provider === 'openai') {
                        // ✅ CRITICAL FIX: Correct parsing for Responses API
                        
                        // Method 1: response.output_text.delta event (MAIN ONE)
                        if (isset($decoded['type']) && $decoded['type'] === 'response.output_text.delta') {
                            // ✅ FIX: delta is the direct text content, not delta.text
                            $content = $decoded['delta'] ?? '';
                            
                            Log::debug("OpenAI Response API delta captured", [
                                'model' => $model,
                                'type' => $decoded['type'],
                                'delta_length' => strlen($content)
                            ]);
                        }
                        
                        // Method 2: Handle full output items
                        if (isset($decoded['output'])) {
                            foreach ($decoded['output'] as $item) {
                                if ($item['type'] === 'message' && isset($item['content'])) {
                                    foreach ($item['content'] as $contentPart) {
                                        if ($contentPart['type'] === 'output_text') {
                                            $content .= $contentPart['text'] ?? '';
                                        }
                                    }
                                }
                            }
                        }
                        
                        // ✅ Capture token usage from response.completed event
                        if (isset($decoded['type']) && $decoded['type'] === 'response.completed') {
                            if (isset($decoded['response']['usage'])) {
                                $usage = $decoded['response']['usage'];
                                // Use total_tokens directly (simpler and more accurate)
                                self::$simultaneousTokenUsage[$model] = $usage['total_tokens'];
                                
                                Log::info("OpenAI Responses API token usage captured", [
                                    'model' => $model,
                                    'input_tokens' => $usage['input_tokens'] ?? 0,
                                    'output_tokens' => $usage['output_tokens'] ?? 0,
                                    'total_tokens' => $usage['total_tokens'] ?? 0,
                                ]);
                            }
                        }
                        
                        // ✅ FALLBACK: Legacy Chat Completions format
                        if (isset($decoded['choices'][0]['delta']['content'])) {
                            $content = $decoded['choices'][0]['delta']['content'];
                        }
                        
                        if (isset($decoded['usage']['total_tokens'])) {
                            self::$simultaneousTokenUsage[$model] = $decoded['usage']['total_tokens'];
                            
                            Log::info("OpenAI token usage captured (legacy format)", [
                                'model' => $model,
                                'total_tokens' => $decoded['usage']['total_tokens']
                            ]);
                        }
                        
                    } elseif ($provider === 'grok') {
                        if (isset($decoded['choices'][0]['delta']['content'])) {
                            $content = $decoded['choices'][0]['delta']['content'];
                        }
                        
                        // Capture Grok token usage
                        if (isset($decoded['usage']['total_tokens'])) {
                            self::$simultaneousTokenUsage[$model] = $decoded['usage']['total_tokens'];
                            
                            Log::info("Grok token usage captured", [
                                'model' => $model,
                                'total_tokens' => $decoded['usage']['total_tokens']
                            ]);
                        }
                    }

                    if ($content !== '') {
                        self::$simultaneousResponses[$model] .= $content;

                        echo "data: " . json_encode([
                            'type' => 'chunk',
                            'model' => $model,
                            'content' => $content,
                            'full_response' => self::$simultaneousResponses[$model],
                            'provider' => $provider
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                    }
                    
                } catch (\Exception $e) {
                    Log::error("Error parsing JSON for {$model} ({$provider}): " . $e->getMessage());
                }
            }
        }

        return strlen($data);
    }

    private function handleMultiModelImageGeneration(Request $request, User $user, $selectedModels, $prompt)
    {
        $creditCheck = checkUserHasCredits();
        if (!$creditCheck['status']) {
            return response()->json([
                'error' => $creditCheck['message']
            ], 400);
        }

        Log::info('Multi-model image generation requested', [
            'prompt' => $prompt,
            'models' => $selectedModels,
            'model_count' => count($selectedModels),
            'has_file' => $request->hasFile('pdf'),
            'optimization_mode' => $request->input('optimization_mode', 'fixed') // ✅ ADD THIS
        ]);

        // ✅ NEW: Get optimization mode to handle Smart modes properly
        $optimizationMode = $request->input('optimization_mode', 'fixed');

        // ✅ Validate that at least one model supports image generation
        // Skip placeholder models (smart_all_auto, provider_smart_panel)
        $supportedModels = array_filter($selectedModels, function($model) {
            // Skip placeholder models
            if ($model === 'smart_all_auto' || str_ends_with($model, '_smart_panel')) {
                return false;
            }
            return $this->supportsImageGeneration($model);
        });

        // ✅ For smart modes, we'll validate after optimization, so don't block here
        if (empty($supportedModels) && $optimizationMode === 'fixed') {
            Log::warning('No models support image generation in fixed mode', [
                'models' => $selectedModels
            ]);
        } elseif (!empty($supportedModels)) {
            Log::info('Image generation capable models', [
                'supported' => $supportedModels,
                'count' => count($supportedModels)
            ]);
        } elseif ($optimizationMode !== 'fixed') {
            Log::info('Smart mode image generation - will validate after optimization', [
                'mode' => $optimizationMode
            ]);
        }

        $conversationId = $request->input('conversation_id');
        
        // ✅ NEW: Check for uploaded image
        $uploadedImagePath = null;
        $uploadedImageUrl = null;
        
        if ($request->hasFile('pdf') && $request->file('pdf')->isValid()) {
            $file = $request->file('pdf');
            $extension = strtolower($file->getClientOriginalExtension());
            
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $tempPath = $file->storeAs('temp', uniqid() . '.' . $extension);
                $uploadedImagePath = storage_path("app/{$tempPath}");
                
                try {
                    $blobClient = BlobRestProxy::createBlobService(config('filesystems.disks.azure.connection_string'));
                    $containerName = config('filesystems.disks.azure.container');
                    $azureFileName = 'chattermate-multi-compare-uploads/' . uniqid() . '_' . $file->getClientOriginalName();
                    $fileContent = file_get_contents($uploadedImagePath);
                    $blobClient->createBlockBlob($containerName, $azureFileName, $fileContent);
                    
                    $baseUrl = rtrim(config('filesystems.disks.azure.url'), '/');
                    $uploadedImageUrl = "{$baseUrl}/{$containerName}/{$azureFileName}";
                    
                    Log::info('Uploaded image for editing', [
                        'local_path' => $uploadedImagePath,
                        'azure_url' => $uploadedImageUrl
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to upload image to Azure: ' . $e->getMessage());
                }
            }
        }
        
        // Get or create conversation
        $conversation = null;
        if ($conversationId) {
            $conversation = MultiCompareConversation::where('id', $conversationId)
                ->where('user_id', $user->id)
                ->first();
        }

        if (!$conversation) {
            $conversation = MultiCompareConversation::create([
                'user_id' => $user->id,
                'title' => $this->generateConversationTitle($prompt),
                'selected_models' => $selectedModels,
                'optimization_mode' => $optimizationMode, // ✅ SAVE MODE
            ]);
        }
                
        // ✅ NEW: Get conversation history for multi-turn editing
        $conversationHistory = [];
        if ($conversation) {
            $messages = $conversation->messages()->orderBy('created_at')->get();
            foreach ($messages as $msg) {
                if ($msg->role === 'assistant' && $msg->all_responses) {
                    foreach ($selectedModels as $model) {
                        if (isset($msg->all_responses[$model])) {
                            $conversationHistory[] = [
                                'role' => 'assistant',
                                'content' => $msg->all_responses[$model],
                                'model' => $model
                            ];
                            break;
                        }
                    }
                }
            }
        }

        return response()->stream(function () use ($selectedModels, $prompt, $user, $conversation, $uploadedImagePath, $uploadedImageUrl, $conversationHistory, $optimizationMode) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            $allResponses = [];
            
            // ✅ NEW: Build model mapping for Smart modes
            $modelMapping = []; // Maps actual model -> frontend panel ID
            
            if ($optimizationMode === 'smart_same') {
                // For Smart (Same), map each model to its provider panel
                foreach ($selectedModels as $model) {
                    // Find provider from model name
                    $provider = $this->getProviderFromModel($model);
                    $panelId = "{$provider}_smart_panel";
                    $modelMapping[$model] = $panelId;
                    
                    Log::info('Smart (Same) model mapping', [
                        'actual_model' => $model,
                        'panel_id' => $panelId,
                        'provider' => $provider
                    ]);
                }
            } elseif ($optimizationMode === 'smart_all') {
                // For Smart (All), all models map to single panel
                foreach ($selectedModels as $model) {
                    $modelMapping[$model] = 'smart_all_auto';
                }
            } else {
                // For Fixed mode, model = panel
                foreach ($selectedModels as $model) {
                    $modelMapping[$model] = $model;
                }
            }
            
            $modeMessage = 'Starting image generation...';
            if ($uploadedImagePath) {
                $modeMessage = 'Starting image editing...';
            } elseif (!empty($conversationHistory)) {
                $modeMessage = 'Refining previous image...';
            }

            echo "data: " . json_encode([
                'type' => 'init',
                'models' => $selectedModels,
                'conversation_id' => $conversation->id,
                'hex_code' => $conversation->hex_code,  // ✅ ADD THIS
                'message' => $modeMessage,
                'mode' => $uploadedImagePath ? 'editing' : (!empty($conversationHistory) ? 'refining' : 'generating'),
                'optimization_mode' => $optimizationMode, // ✅ SEND MODE
                'model_mapping' => $modelMapping // ✅ SEND MAPPING
            ]) . "\n\n";
            ob_flush();
            flush();

            foreach ($selectedModels as $model) {
                try {
                    // ✅ Get the frontend panel ID for this model
                    $panelId = $modelMapping[$model] ?? $model;
                    
                    Log::info("Processing image generation for model", [
                        'actual_model' => $model,
                        'panel_id' => $panelId,
                        'uploaded_image' => !empty($uploadedImagePath),
                        'history_count' => count($conversationHistory)
                    ]);

                    echo "data: " . json_encode([
                        'type' => 'model_start',
                        'model' => $panelId, // ✅ USE PANEL ID
                        'actual_model' => $model, // ✅ ALSO SEND ACTUAL MODEL
                        'message' => $uploadedImagePath ? 'Editing image...' : 'Generating image...'
                    ]) . "\n\n";
                    ob_flush();
                    flush();

                    // Check if model supports image generation
                    if (!$this->supportsImageGeneration($model)) {
                        $errorMessage = "Image generation is not supported for model: {$model}";
                        
                        Log::warning($errorMessage, ['model' => $model, 'panel_id' => $panelId]);
                        
                        echo "data: " . json_encode([
                            'type' => 'error',
                            'model' => $panelId, // ✅ USE PANEL ID
                            'actual_model' => $model,
                            'error' => $errorMessage
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                        
                        $allResponses[$model] = $errorMessage;
                        continue;
                    }

                    // Generate or edit image based on model type
                    $imageSource = null;
                    $tempFile = null;

                    if ($this->isGrokModel($model)) {
                        if ($uploadedImagePath) {
                            $errorMessage = "Grok doesn't support image editing yet. Only generation.";
                            $allResponses[$model] = $errorMessage;
                            
                            Log::info('Grok image editing not supported', ['model' => $model]);
                            
                            echo "data: " . json_encode([
                                'type' => 'error',
                                'model' => $panelId, // ✅ USE PANEL ID
                                'actual_model' => $model,
                                'error' => $errorMessage
                            ]) . "\n\n";
                            ob_flush();
                            flush();
                            continue;
                        }
                        
                        Log::info('Generating Grok image', ['model' => $model, 'prompt' => substr($prompt, 0, 50)]);
                        $imageSource = $this->generateGrokImage($prompt);
                        $imageContent = file_get_contents($imageSource);
                        
                    } elseif ($this->isGeminiModel($model)) {
                        Log::info('Generating Gemini image', [
                            'model' => $model,
                            'has_input_image' => !empty($uploadedImagePath),
                            'has_history' => !empty($conversationHistory)
                        ]);
                        
                        $inputImage = $uploadedImagePath;
                        
                        $historyForModel = null;
                        if (!$inputImage && !empty($conversationHistory)) {
                            $historyForModel = $conversationHistory;
                        }
                        
                        $tempFile = $this->generateGeminiImage($prompt, $inputImage, $historyForModel);
                        $imageContent = file_get_contents($tempFile);
                        
                    } else {
                        // OpenAI - doesn't support editing
                        if ($uploadedImagePath) {
                            $errorMessage = "OpenAI/DALL-E doesn't support image editing. Only generation.";
                            $allResponses[$model] = $errorMessage;
                            
                            Log::info('OpenAI image editing not supported', ['model' => $model]);
                            
                            echo "data: " . json_encode([
                                'type' => 'error',
                                'model' => $panelId, // ✅ USE PANEL ID
                                'actual_model' => $model,
                                'error' => $errorMessage
                            ]) . "\n\n";
                            ob_flush();
                            flush();
                            continue;
                        }
                        
                        Log::info('Generating OpenAI image', ['model' => $model, 'prompt' => substr($prompt, 0, 50)]);
                        $imageSource = $this->generateOpenAIImage($prompt);
                        $imageContent = file_get_contents($imageSource);
                    }

                    if ($imageContent) {
                        Log::info('Image generated successfully', [
                            'model' => $model,
                            'panel_id' => $panelId,
                            'size' => strlen($imageContent)
                        ]);
                        
                        // Upload to Azure
                        $blobClient = BlobRestProxy::createBlobService(config('filesystems.disks.azure.connection_string'));
                        $containerName = config('filesystems.disks.azure.container');
                        
                        // Determine file extension
                        $extension = 'png';
                        if ($this->isGeminiModel($model)) {
                            $extension = 'png';
                        } elseif ($this->isGrokModel($model)) {
                            $extension = 'jpg';
                        }
                        
                        $imageName = 'chattermate-multi-compare-images/' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $model) . '.' . $extension;
                        $blobClient->createBlockBlob($containerName, $imageName, $imageContent);

                        $baseUrl = rtrim(config('filesystems.disks.azure.url'), '/');
                        $publicUrl = "{$baseUrl}/{$containerName}/{$imageName}";

                        $allResponses[$model] = $publicUrl;

                        Log::info('Image uploaded to Azure', [
                            'model' => $model,
                            'panel_id' => $panelId,
                            'url' => $publicUrl
                        ]);

                        echo "data: " . json_encode([
                            'type' => 'complete',
                            'model' => $panelId, // ✅ USE PANEL ID
                            'actual_model' => $model, // ✅ ALSO SEND ACTUAL MODEL
                            'image' => $publicUrl,
                            'prompt' => $prompt,
                            'was_editing' => !empty($uploadedImagePath)
                        ]) . "\n\n";
                        ob_flush();
                        flush();

                        // Deduct credits per image based on model
                        if ($this->isGeminiModel($model)) {
                            // Gemini models have different credit costs based on model type
                            $credits = calculateCreditsForGemini($model);
                            deductUserTokensAndCredits(0, $credits);
                        } elseif ($this->isGrokModel($model)) {
                            // Grok-2-image = 8 credits
                            deductUserTokensAndCredits(0, 8);
                        } else {
                            // OpenAI DALL-E 3 = 8 credits
                            deductUserTokensAndCredits(0, 8);
                        }
                    } else {
                        throw new \Exception('Failed to get image content');
                    }

                    // Clean up temp file
                    if ($tempFile && file_exists($tempFile)) {
                        unlink($tempFile);
                    }

                } catch (\Exception $e) {
                    $panelId = $modelMapping[$model] ?? $model;
                    
                    Log::error("Error generating image for model {$model}", [
                        'panel_id' => $panelId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    $errorMessage = 'Image generation failed: ' . $e->getMessage();
                    $allResponses[$model] = $errorMessage;
                    
                    echo "data: " . json_encode([
                        'type' => 'error',
                        'model' => $panelId, // ✅ USE PANEL ID
                        'actual_model' => $model,
                        'error' => $errorMessage
                    ]) . "\n\n";
                    ob_flush();
                    flush();
                    
                    // Clean up temp file on error
                    if (isset($tempFile) && $tempFile && file_exists($tempFile)) {
                        unlink($tempFile);
                    }
                }
            }
            
            // Clean up uploaded image temp file
            if ($uploadedImagePath && file_exists($uploadedImagePath)) {
                unlink($uploadedImagePath);
            }

            // Save to database (with upload info if present)
            try {
                $uploadInfo = null;
                if ($uploadedImageUrl) {
                    $uploadInfo = [
                        'file_path' => $uploadedImageUrl,
                        'file_name' => 'uploaded_image.png',
                    ];
                }
                
                $this->saveMultiCompareConversation($conversation, $prompt, $allResponses, $uploadInfo, null);
            } catch (\Exception $e) {
                Log::error('Error saving image generation conversation: ' . $e->getMessage());
            }

            echo "data: " . json_encode([
                'type' => 'all_complete',
                'conversation_id' => $conversation->id,
                'hex_code' => $conversation->hex_code  // ✅ ADD THIS
            ]) . "\n\n";
            
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    // ============================================================================
    // HELPER METHOD: Get provider from model name
    // ============================================================================

    /**
     * Determine the provider from a model name
     */
    private function getProviderFromModel($model)
    {
        $modelLower = strtolower($model);
        
        if (str_contains($modelLower, 'gemini')) {
            return 'gemini';
        } elseif (str_contains($modelLower, 'claude')) {
            return 'claude';
        } elseif (str_contains($modelLower, 'grok')) {
            return 'grok';
        } else {
            return 'openai';
        }
    }

    // Helper method to generate OpenAI image
    private function generateOpenAIImage($prompt)
    {
        $response = OpenAI::images()->create([
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'quality' => 'hd',
            'style' => 'vivid',
        ]);

        return $response->data[0]->url;
    }

    // Helper method to generate Grok image
    private function generateGrokImage($prompt)
    {
        $apiKey = config('services.xai.api_key');
        
        $ch = curl_init('https://api.x.ai/v1/images/generations');
        
        $payload = [
            'model' => 'grok-2-image-1212',
            'prompt' => $prompt,
            'image_format' => 'url'
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Grok API returned error: ' . $httpCode);
        }

        $data = json_decode($response, true);
        
        if (!isset($data['data'][0]['url'])) {
            throw new \Exception('Unexpected API response format');
        }
        
        return $data['data'][0]['url'];
    }

    // Generate image using Gemini 2.5 Flash Image model
    private function generateGeminiImage($prompt, $imagePath = null, $conversationHistory = null)
    {
        $apiKey = config('services.gemini.api_key');
        
        // Use the gemini-2.5-flash-image model
        $model = 'gemini-2.5-flash-image';
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        
        // Build the content parts
        $parts = [];
        
        // ✅ NEW: Check for previous image in conversation (for multi-turn editing)
        if (!$imagePath && $conversationHistory) {
            $previousImage = $this->getPreviousImageFromConversation($conversationHistory);
            if ($previousImage) {
                $imagePath = $previousImage;
            }
        }
        
        // ✅ NEW: If image is provided, add it first
        if ($imagePath) {
            $imageBase64 = $this->convertImageToBase64($imagePath);
            
            if ($imageBase64) {
                // Determine MIME type
                $mimeType = 'image/jpeg';
                if (is_string($imagePath)) {
                    $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
                    $mimeType = match($extension) {
                        'png' => 'image/png',
                        'jpg', 'jpeg' => 'image/jpeg',
                        'gif' => 'image/gif',
                        'webp' => 'image/webp',
                        default => 'image/jpeg',
                    };
                }
                
                $parts[] = [
                    'inlineData' => [
                        'mimeType' => $mimeType,
                        'data' => $imageBase64
                    ]
                ];
                
                Log::info('Gemini image editing mode', [
                    'has_input_image' => true,
                    'mime_type' => $mimeType
                ]);
            }
        }
        
        // Add text prompt
        $parts[] = ['text' => $prompt];
        
        $payload = [
            'contents' => [
                [
                    'parts' => $parts,
                    'role' => 'user'
                ]
            ],
            'generationConfig' => [
                'responseModalities' => ['IMAGE'], // Only need IMAGE for editing/generation
                'temperature' => 1.0,
                'topK' => 40,
                'topP' => 0.95,
            ]
        ];
        
        Log::info('Gemini API request', [
            'has_image' => !empty($imagePath),
            'parts_count' => count($parts),
            'prompt_length' => strlen($prompt)
        ]);
        
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 90, // Increased for image editing
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('Gemini API request failed: ' . $error);
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            Log::error('Gemini image generation failed', [
                'http_code' => $httpCode,
                'response' => substr($response, 0, 500)
            ]);
            throw new \Exception('Gemini API returned error: ' . $httpCode);
        }

        $data = json_decode($response, true);
        
        if (!isset($data['candidates'][0]['content']['parts'])) {
            throw new \Exception('Unexpected API response format from Gemini');
        }
        
        // Find the image part
        $imagePart = null;
        foreach ($data['candidates'][0]['content']['parts'] as $part) {
            if (isset($part['inlineData']['data'])) {
                $imagePart = $part;
                break;
            }
        }
        
        if (!$imagePart) {
            throw new \Exception('No image data found in Gemini response');
        }
        
        // Decode base64 image
        $imageData = base64_decode($imagePart['inlineData']['data']);
        
        if ($imageData === false) {
            throw new \Exception('Failed to decode Gemini image data');
        }
        
        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'gemini_image_');
        file_put_contents($tempFile, $imageData);
        
        Log::info('Gemini image generated successfully', [
            'temp_file' => $tempFile,
            'size' => strlen($imageData)
        ]);
        
        return $tempFile;
    }

    /**
     * Convert image to base64 for Gemini API
     * Handles local files, URLs, and temp files
     * 
     * @param string $imagePath Path to image (local file or URL)
     * @return string|null Base64 encoded image data (without data URL prefix)
     */
    private function convertImageToBase64($imagePath)
    {
        try {
            $imageData = null;
            
            // Check if it's a URL
            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                // Download from URL
                $imageData = file_get_contents($imagePath);
                
                if ($imageData === false) {
                    Log::warning('Failed to download image from URL', ['url' => $imagePath]);
                    return null;
                }
            } 
            // Check if it's a local file
            elseif (file_exists($imagePath)) {
                $imageData = file_get_contents($imagePath);
                
                if ($imageData === false) {
                    Log::warning('Failed to read image file', ['path' => $imagePath]);
                    return null;
                }
            }
            else {
                Log::warning('Image path not found', ['path' => $imagePath]);
                return null;
            }
            
            // Encode to base64
            return base64_encode($imageData);
            
        } catch (\Exception $e) {
            Log::error('Error converting image to base64', [
                'error' => $e->getMessage(),
                'path' => $imagePath
            ]);
            return null;
        }
    }

    /**
     * Get the previous image URL from conversation history
     * Used for multi-turn image editing
     * 
     * @param array $conversationHistory Array of conversation messages
     * @return string|null URL of the most recent image
     */
    private function getPreviousImageFromConversation($conversationHistory)
    {
        if (empty($conversationHistory)) {
            return null;
        }
        
        // Search backwards through conversation for the most recent image
        for ($i = count($conversationHistory) - 1; $i >= 0; $i--) {
            $message = $conversationHistory[$i];
            
            // Check if it's an assistant message with image URL
            if (isset($message['role']) && $message['role'] === 'assistant') {
                if (isset($message['content']) && $this->isImageURL($message['content'])) {
                    Log::info('Found previous image in conversation', [
                        'image_url' => $message['content']
                    ]);
                    return $message['content'];
                }
            }
        }
        
        return null;
    }

    /**
     * Check if a string is an image URL
     * 
     * @param string $str String to check
     * @return bool True if string is an image URL
     */
    private function isImageURL($str)
    {
        if (!is_string($str) || empty($str)) {
            return false;
        }
        
        // Check if it's a URL
        if (!filter_var($str, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check if URL ends with image extension
        $path = parse_url($str, PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Validate file security before processing
     * Checks for malicious files, size limits, and proper MIME types
     */
    private function validateFileSecurity($file, $extension, $filename)
    {
        // ✅ 1. Check file size (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if ($file->getSize() > $maxSize) {
            return [
                'valid' => false,
                'error' => "File '{$filename}' exceeds maximum size of 10MB"
            ];
        }

        // ✅ 2. Validate against allowed extensions
        $allowedExtensions = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'webp', 'gif'];
        if (!in_array($extension, $allowedExtensions)) {
            return [
                'valid' => false,
                'error' => "File type '.{$extension}' is not allowed. Allowed types: " . implode(', ', $allowedExtensions)
            ];
        }

        // ✅ 3. Validate MIME type matches extension
        $mimeType = $file->getMimeType();
        $validMimeTypes = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'png' => ['image/png'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'webp' => ['image/webp'],
            'gif' => ['image/gif']
        ];

        if (isset($validMimeTypes[$extension]) && !in_array($mimeType, $validMimeTypes[$extension])) {
            return [
                'valid' => false,
                'error' => "File '{$filename}' has invalid MIME type. Expected " . implode(' or ', $validMimeTypes[$extension]) . ", got {$mimeType}"
            ];
        }

        // ✅ 4. Check for suspicious filenames (path traversal attacks)
        if (preg_match('/\.\.\/|\.\.\\\\/', $filename)) {
            Log::warning('File upload blocked: Path traversal attempt', [
                'filename' => $filename,
                'ip' => request()->ip()
            ]);
            return [
                'valid' => false,
                'error' => 'Invalid filename'
            ];
        }

        // ✅ 5. Check for double extensions (e.g., file.pdf.exe)
        $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        if (preg_match('/\.(exe|bat|cmd|sh|php|js|jar|vbs|scr|msi|app|deb|rpm)$/i', $filenameWithoutExt)) {
            Log::warning('File upload blocked: Double extension detected', [
                'filename' => $filename,
                'ip' => request()->ip()
            ]);
            return [
                'valid' => false,
                'error' => 'Suspicious file detected'
            ];
        }

        // ✅ 6. For images, validate actual image content
        if (in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif'])) {
            try {
                $imageInfo = getimagesize($file->getRealPath());
                if ($imageInfo === false) {
                    return [
                        'valid' => false,
                        'error' => "File '{$filename}' is not a valid image"
                    ];
                }

                // Check image dimensions (max 8000x8000)
                if ($imageInfo[0] > 8000 || $imageInfo[1] > 8000) {
                    return [
                        'valid' => false,
                        'error' => "Image dimensions too large. Maximum: 8000x8000 pixels"
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'valid' => false,
                    'error' => "Failed to validate image: " . $e->getMessage()
                ];
            }
        }

        // ✅ 7. For PDFs, basic validation
        if ($extension === 'pdf') {
            $content = file_get_contents($file->getRealPath(), false, null, 0, 1024);
            if (strpos($content, '%PDF-') !== 0) {
                return [
                    'valid' => false,
                    'error' => "File '{$filename}' is not a valid PDF"
                ];
            }
        }

        // ✅ All checks passed
        return ['valid' => true];
    }

    // Extract text from PDF - works with or without Ghostscript
    private function extractTextFromPDF($filePath)
    {
        try {
            // First, try to extract selectable text
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            // If we got substantial text, return it
            if (strlen(trim($text)) > 100) {
                Log::info('PDF text extracted successfully (selectable text)', [
                    'length' => strlen($text)
                ]);
                return substr($text, 0, 8000);
            }
            
            // If no text or very little text, it might be image-based PDF
            Log::info('PDF has little/no selectable text, attempting OCR', [
                'text_length' => strlen($text)
            ]);
            
            // Try to convert PDF to images (if Ghostscript available)
            $ocrText = $this->extractTextFromPDFWithOCR($filePath);
            
            if (!empty($ocrText)) {
                Log::info('PDF text extracted via OCR', [
                    'length' => strlen($ocrText)
                ]);
                return $ocrText;
            }
            
            // Fallback to whatever text we got
            return substr($text, 0, 8000);
            
        } catch (\Exception $e) {
            Log::error('PDF extraction error: ' . $e->getMessage());
            return '[Error extracting PDF content]';
        }
    }

    // Extract text from PDF using OCR (for image-based PDFs)
    private function extractTextFromPDFWithOCR($filePath)
    {
        try {
            // Check if we can convert PDF to images
            if ($this->canConvertPDFToImages()) {
                // Method 1: Convert to images then OCR
                $pdfImages = $this->convertPDFToImages($filePath);
                
                if (!empty($pdfImages)) {
                    return $this->processImagesForOCR($pdfImages);
                }
            }
            
            // Method 2: Direct PDF to AI (fallback - works everywhere)
            Log::info('Using direct PDF-to-AI processing (no image conversion)');
            return $this->extractTextFromPDFDirectly($filePath);
            
        } catch (\Exception $e) {
            Log::error('OCR extraction error: ' . $e->getMessage());
            return '';
        }
    }

    // Check if PDF to image conversion is possible
    private function canConvertPDFToImages()
    {
        // Check if Imagick is available
        if (class_exists('Imagick')) {
            Log::info('Imagick available for PDF conversion');
            return true;
        }
        
        // Check if Ghostscript is available
        $gsPath = $this->getGhostscriptPath();
        if ($gsPath !== false) {
            Log::info('Ghostscript available for PDF conversion');
            return true;
        }
        
        Log::warning('Neither Imagick nor Ghostscript available');
        return false;
    }

    // Process multiple images for OCR
    private function processImagesForOCR($pdfImages)
    {
        $allText = [];
        $maxPages = 10;
        
        foreach (array_slice($pdfImages, 0, $maxPages) as $pageNum => $imagePath) {
            try {
                $pageText = $this->extractTextFromImageUsingAI($imagePath);
                if (!empty($pageText)) {
                    $allText[] = "=== Page " . ($pageNum + 1) . " ===\n" . $pageText;
                }
                
                // Clean up temp image file
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            } catch (\Exception $e) {
                Log::error("Error extracting text from page " . ($pageNum + 1) . ": " . $e->getMessage());
            }
        }
        
        $combinedText = implode("\n\n", $allText);
        return substr($combinedText, 0, 8000);
    }

    // Extract text from PDF directly using AI (no image conversion needed)
    // This works on ANY server, including those without Ghostscript/Imagick
    private function extractTextFromPDFDirectly($pdfPath)
    {
        try {
            // Convert PDF to base64
            $pdfData = file_get_contents($pdfPath);
            $base64PDF = base64_encode($pdfData);
            $fileSize = strlen($pdfData);
            
            Log::info('Attempting direct PDF AI extraction', [
                'size' => $fileSize,
                'size_mb' => round($fileSize / 1024 / 1024, 2)
            ]);
            
            // Check file size (most APIs have limits)
            if ($fileSize > 10 * 1024 * 1024) { // 10MB
                Log::warning('PDF too large for direct AI processing');
                return '';
            }
            
            // Try Gemini first (supports PDF files directly)
            try {
                return $this->extractTextFromPDFWithGemini($base64PDF);
            } catch (\Exception $e) {
                Log::warning('Gemini PDF extraction failed: ' . $e->getMessage());
            }
            
            // Fallback: Try Claude (also supports PDFs)
            try {
                return $this->extractTextFromPDFWithClaude($base64PDF);
            } catch (\Exception $e) {
                Log::warning('Claude PDF extraction failed: ' . $e->getMessage());
            }
            
            return '';
            
        } catch (\Exception $e) {
            Log::error('Direct PDF extraction error: ' . $e->getMessage());
            return '';
        }
    }

    // Extract text from PDF using Gemini (supports PDF files directly)
    private function extractTextFromPDFWithGemini($base64PDF)
    {
        $apiKey = config('services.gemini.api_key');
        $model = 'gemini-2.0-flash-exp';
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inlineData' => [
                                'mimeType' => 'application/pdf',
                                'data' => $base64PDF
                            ]
                        ],
                        [
                            'text' => 'Extract all text from this PDF document. Return ONLY the extracted text, preserving the structure and formatting as much as possible. If there is no text, return "No text found".'
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 8000,
            ]
        ];
        
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60, // Longer timeout for PDFs
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception("Gemini API error: HTTP {$httpCode} - " . substr($response, 0, 200));
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $extractedText = trim($data['candidates'][0]['content']['parts'][0]['text']);
            Log::info('Gemini PDF extraction successful', [
                'length' => strlen($extractedText)
            ]);
            return $extractedText;
        }
        
        throw new \Exception('No text in Gemini response');
    }

    // Extract text from PDF using Claude (supports PDF files directly)
    private function extractTextFromPDFWithClaude($base64PDF)
    {
        $apiKey = config('services.anthropic.api_key');
        $endpoint = 'https://api.anthropic.com/v1/messages';
        
        $payload = [
            'model' => 'claude-3-5-haiku-20241022', // Cheapest model
            'max_tokens' => 8000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'document',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => 'application/pdf',
                                'data' => $base64PDF
                            ]
                        ],
                        [
                            'type' => 'text',
                            'text' => 'Extract all text from this PDF document. Return ONLY the extracted text, preserving structure. If no text, return "No text found".'
                        ]
                    ]
                ]
            ]
        ];
        
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 60,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception("Claude API error: HTTP {$httpCode}");
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['content'][0]['text'])) {
            $extractedText = trim($data['content'][0]['text']);
            Log::info('Claude PDF extraction successful', [
                'length' => strlen($extractedText)
            ]);
            return $extractedText;
        }
        
        throw new \Exception('No text in Claude response');
    }

    // Auto-detect Ghostscript binary path
    private function getGhostscriptPath()
    {
        $possiblePaths = [
            'C:/Program Files/gs/gs10.02.1/bin/',
            'C:/Program Files/gs/gs10.01.0/bin/',
            'C:/Program Files/gs/gs9.56.1/bin/',
            '/usr/bin/',
            '/usr/local/bin/',
        ];
        
        foreach ($possiblePaths as $path) {
            try {
                $gsExecutable = $path . (PHP_OS_FAMILY === 'Windows' ? 'gswin64c.exe' : 'gs');
                if (file_exists($gsExecutable)) {
                    return $path;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Check system PATH
        try {
            $output = shell_exec('gs --version 2>&1');
            if ($output && strpos($output, 'Ghostscript') !== false) {
                return null; // Use system PATH
            }
        } catch (\Exception $e) {
            // Continue
        }
        
        return false;
    }

    // Convert PDF to images (requires Ghostscript or Imagick)
    private function convertPDFToImages($pdfPath)
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($pdfPath);
            $pageCount = count($pdf->getPages());
            
            if ($pageCount === 0) {
                return [];
            }
            
            $imagePaths = [];
            $maxPages = min($pageCount, 10);
            
            $pdfToImage = new \Spatie\PdfToImage\Pdf($pdfPath);
            
            $gsPath = $this->getGhostscriptPath();
            if ($gsPath !== false && $gsPath !== null) {
                $pdfToImage->setBinPath($gsPath);
            }
            
            for ($pageNum = 1; $pageNum <= $maxPages; $pageNum++) {
                $tempImagePath = sys_get_temp_dir() . '/pdf_page_' . uniqid() . '.jpg';
                
                $pdfToImage->setPage($pageNum)
                    ->setOutputFormat('jpg')
                    ->setResolution(150)
                    ->saveImage($tempImagePath);
                
                if (file_exists($tempImagePath) && filesize($tempImagePath) > 0) {
                    $imagePaths[] = $tempImagePath;
                }
            }
            
            return $imagePaths;
            
        } catch (\Exception $e) {
            Log::error('PDF to image conversion failed: ' . $e->getMessage());
            return [];
        }
    }

    // Add this method to extract text from DOCX
    private function extractTextFromDOCX($filePath)
    {
        try {
            $phpWord = WordIOFactory::load($filePath);
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $child) {
                            if (method_exists($child, 'getText')) {
                                $text .= $child->getText() . "\n";
                            }
                        }
                    }
                }
            }
            
            return substr($text, 0, 8000);
        } catch (\Exception $e) {
            Log::error('DOCX extraction error: ' . $e->getMessage());
            return '[Error extracting DOCX content]';
        }
    }


    private function saveMultiCompareConversation($conversation, $userMessage, $allResponses, $uploadedFiles = [], $fileContentMessages = [])
    {
        try {
            Log::info('Starting to save multi-compare conversation', [
                'conversation_id' => $conversation->id,
                'user_message_length' => strlen($userMessage),
                'responses_count' => count($allResponses),
                'has_file' => !is_null($uploadedFiles),
                'responses_summary' => array_map(function($response) {
                    return [
                        'length' => strlen($response),
                        'preview' => substr($response, 0, 50)
                    ];
                }, $allResponses)
            ]);

            // Save user message
            $userMessageRecord = MultiCompareMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $userMessage,
            ]);

            Log::info('User message saved', ['message_id' => $userMessageRecord->id]);

            // ✅ Save multiple file attachments if present
            if (!empty($uploadedFiles) && $userMessageRecord) {
                foreach ($uploadedFiles as $fileData) {
                    MultiCompareAttachment::create([
                        'message_id' => $userMessageRecord->id,
                        'file_path' => $fileData['file_path'],
                        'file_name' => $fileData['file_name'],
                        'file_type' => $fileData['file_type'],
                    ]);
                }
                
                Log::info('Multiple attachments saved successfully', [
                    'message_id' => $userMessageRecord->id,
                    'attachment_count' => count($uploadedFiles),
                    'files' => array_column($uploadedFiles, 'file_name')
                ]);
            }

            // ✅ Save assistant responses - CRITICAL FIX
            if (!empty($allResponses)) {
                $assistantMessage = MultiCompareMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => json_encode($allResponses),
                    'all_responses' => $allResponses,
                ]);
                
                Log::info('Assistant responses saved successfully', [
                    'conversation_id' => $conversation->id,
                    'message_id' => $assistantMessage->id,
                    'response_count' => count($allResponses),
                    'models' => array_keys($allResponses),
                    'content_length' => strlen($assistantMessage->content)
                ]);
            } else {
                Log::error('No responses to save - allResponses is empty!', [
                    'conversation_id' => $conversation->id,
                    'allResponses' => $allResponses
                ]);
            }

            // Update conversation title if it's still default
            if ($conversation->title === 'New Comparison' || $conversation->title === 'Untitled Comparison') {
                $conversation->update([
                    'title' => $this->generateConversationTitle($userMessage)
                ]);
            }

            // Update conversation timestamp
            $conversation->touch();

            Log::info('Multi-compare conversation saved successfully', [
                'conversation_id' => $conversation->id,
                'total_messages' => $conversation->messages()->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving multi-compare conversation', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'allResponses_keys' => array_keys($allResponses ?? []),
            ]);
            throw $e;
        }
    }

    private function generateConversationTitle($message)
    {
        // Generate a meaningful title from the first message
        $title = Str::limit($message, 50, '...');
        $title = trim(preg_replace('/[^\w\s-]/', '', $title));
        
        if (empty($title)) {
            $title = 'Untitled Comparison';
        }
        
        return $title;
    }

    // Update getMultiCompareChats to support archived filter
    public function getMultiCompareChats(Request $request)
    {
        $showArchived = $request->input('show_archived', false);
        
        $query = MultiCompareConversation::where('user_id', auth()->id())
            ->with(['messages' => function($query) {
                $query->where('role', 'user')->latest()->limit(1);
            }])
            ->withCount('messages')
            ->orderBy('updated_at', 'desc');
        
        // Filter by archived status
        if ($showArchived === 'only') {
            $query->where('archived', true);
        } elseif ($showArchived === false || $showArchived === 'false') {
            $query->where('archived', false);
        }
        // If 'all', don't filter
        
        $conversations = $query->get();

        return response()->json($conversations->map(function ($conversation) {
            $lastUserMessage = $conversation->messages->first();
            
            return [
                'id' => $conversation->id,
                'hex_code' => $conversation->hex_code,
                'title' => $conversation->title,
                'selected_models' => $conversation->selected_models,
                'last_user_message' => $lastUserMessage ? $lastUserMessage->content : null,
                'message_count' => $conversation->messages_count,
                'optimization_mode' => $conversation->optimization_mode ?? 'fixed',
                'archived' => $conversation->archived ?? false,
                'updated_at' => $conversation->updated_at->toISOString(),
            ];
        }));
    }

    public function getMultiCompareConversation($hexCode)
    {
       $conversation = MultiCompareConversation::whereRaw(
            'LOWER(hex_code) = ?',
            [strtolower($hexCode)]
        )
        ->where('user_id', auth()->id())
        ->with(['messages' => function ($query) {
            $query->with('attachments')->orderBy('created_at');
        }])
        ->firstOrFail();

        return response()->json([
            'id' => $conversation->id,
            'hex_code' => $conversation->hex_code,
            'title' => $conversation->title,
            'selected_models' => $conversation->selected_models,
            'optimization_mode' => $conversation->optimization_mode ?? 'fixed', // ✅ ADD THIS
            'messages' => $conversation->messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'all_responses' => $message->all_responses,
                    'created_at' => $message->created_at->toISOString(),
                    // AFTER (PASTE THIS):
                    'attachments' => $message->attachments->map(function ($attachment) {
                        return [
                            'url' => rtrim(config('filesystems.disks.azure.url'), '/') . '/' . 
                                    config('filesystems.disks.azure.container') . '/' . 
                                    $attachment->file_path,
                            'name' => $attachment->file_name,
                            'type' => $attachment->file_type,
                        ];
                    })->toArray()
                ];
            }),
            'updated_at' => $conversation->updated_at->toISOString(),
        ]);
    }

    public function deleteMultiCompareConversation($hexCode)
    {
        $conversation = MultiCompareConversation::where('hex_code', $hexCode)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted successfully']);
    }

    public function updateMultiCompareConversationTitle($hexCode, Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $conversation = MultiCompareConversation::where('hex_code', $hexCode)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $conversation->update([
            'title' => $request->input('title'),
        ]);

        return response()->json(['message' => 'Conversation title updated successfully']);
    }

    /**
     * Export multi-chat conversation as Markdown or JSON
     * Allows users to download full conversation history
     */
    public function exportMultiChatConversation(Request $request, $hexCode)
    {
        try {
            Log::info('Export request received', [
                'hex_code' => $hexCode,
                'format' => $request->input('format'),
                'user_id' => auth()->id()
            ]);

            $request->validate([
                'format' => 'required|in:markdown,json',
            ]);

            $format = $request->input('format');
            /** @var \App\Models\User $user */
            $user = auth()->user();

            // Find the conversation
            $conversation = MultiCompareConversation::where('hex_code', $hexCode)
                ->where('user_id', $user->id)
                ->with(['messages.attachments'])
                ->firstOrFail();

            $messages = $conversation->messages()->orderBy('created_at')->get();

            if ($format === 'markdown') {
                return $this->exportAsMarkdown($conversation, $messages);
            } else {
                return $this->exportAsJSON($conversation, $messages);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Invalid format. Please choose markdown or json.',
                'details' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Conversation not found or you do not have permission to export it.'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error exporting conversation', [
                'hex_code' => $hexCode,
                'format' => $request->input('format'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to export conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export conversation as Markdown file
     */
    private function exportAsMarkdown($conversation, $messages)
    {
        $markdown = "# {$conversation->title}\n\n";
        $markdown .= "**Created:** " . $conversation->created_at->format('F j, Y g:i A') . "\n";
        $markdown .= "**Last Updated:** " . $conversation->updated_at->format('F j, Y g:i A') . "\n";
        $markdown .= "**Models:** " . implode(', ', $conversation->selected_models) . "\n";
        $markdown .= "**Mode:** " . ucfirst(str_replace('_', ' ', $conversation->optimization_mode)) . "\n\n";
        $markdown .= "---\n\n";

        foreach ($messages as $message) {
            if ($message->role === 'user') {
                $markdown .= "## 👤 User\n\n";
                $markdown .= $message->content . "\n\n";

                // Add attachments info
                if ($message->attachments && $message->attachments->count() > 0) {
                    $markdown .= "**Attachments:**\n";
                    foreach ($message->attachments as $attachment) {
                        $markdown .= "- {$attachment->file_name} ({$attachment->file_type})\n";
                    }
                    $markdown .= "\n";
                }
            } elseif ($message->role === 'assistant') {
                $markdown .= "## 🤖 Assistant Responses\n\n";

                if ($message->all_responses && is_array($message->all_responses)) {
                    foreach ($message->all_responses as $model => $response) {
                        // Skip non-model keys like 'files'
                        if ($model === 'files') continue;

                        $markdown .= "### {$model}\n\n";
                        $markdown .= $response . "\n\n";
                    }
                } else {
                    $markdown .= $message->content . "\n\n";
                }
            }

            $markdown .= "---\n\n";
        }

        $markdown .= "\n*Exported from Multi-Chat on " . now()->format('F j, Y g:i A') . "*\n";

        $filename = "multi-chat-" . $conversation->hex_code . ".md";

        return response($markdown, 200, [
            'Content-Type' => 'text/markdown',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export conversation as JSON file
     */
    private function exportAsJSON($conversation, $messages)
    {
        $export = [
            'conversation' => [
                'id' => $conversation->id,
                'hex_code' => $conversation->hex_code,
                'title' => $conversation->title,
                'selected_models' => $conversation->selected_models,
                'optimization_mode' => $conversation->optimization_mode,
                'created_at' => $conversation->created_at->toISOString(),
                'updated_at' => $conversation->updated_at->toISOString(),
            ],
            'messages' => [],
            'metadata' => [
                'total_messages' => $messages->count(),
                'exported_at' => now()->toISOString(),
                'export_version' => '1.0'
            ]
        ];

        foreach ($messages as $message) {
            $messageData = [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
            ];

            if ($message->role === 'assistant' && $message->all_responses) {
                $messageData['all_responses'] = $message->all_responses;
            }

            if ($message->attachments && $message->attachments->count() > 0) {
                $messageData['attachments'] = $message->attachments->map(function ($att) {
                    return [
                        'file_name' => $att->file_name,
                        'file_type' => $att->file_type,
                        'file_path' => $att->file_path,
                        'azure_url' => rtrim(config('filesystems.disks.azure.url'), '/') . '/' .
                            config('filesystems.disks.azure.container') . '/' .
                            $att->file_path,
                    ];
                })->toArray();
            }

            $export['messages'][] = $messageData;
        }

        $filename = "multi-chat-" . $conversation->hex_code . ".json";

        return response()->json($export, 200, [
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function translateText(Request $request)
    {
        try {
            $request->validate([
                'text' => 'required|string',
                'target_lang' => 'required|string'
            ]);

            $text = $request->input('text');
            $targetLang = $request->input('target_lang');
            
            // ✅ FIXED: Remove the extra ->client() call
            $response = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a professional translator. Translate the following text to {$targetLang}. Provide ONLY the translation, without any explanations, notes, or additional text."
                    ],
                    [
                        'role' => 'user',
                        'content' => $text
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.3,
            ]);
            
            $translatedText = $response->choices[0]->message->content;
            
            return response()->json([
                'translatedText' => trim($translatedText)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Translation error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Translation service error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchMultiCompareConversations(Request $request)
    {
        $searchTerm = $request->input('search', '');
        $userId = auth()->id();
        
        if (empty($searchTerm)) {
            // Return all conversations if no search term
            $conversations = DB::table('multi_compare_conversations')
                ->where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($conv) {
                    $conv->selected_models = json_decode($conv->selected_models, true) ?? [];
                    return $conv;
                });
            
            return response()->json($conversations);
        }
        
        // Search in both conversation titles and message content
        $conversations = DB::table('multi_compare_conversations as c')
            ->where('c.user_id', $userId)
            ->where(function($query) use ($searchTerm) {
                // Search in conversation title
                $query->where('c.title', 'LIKE', "%{$searchTerm}%")
                    // OR search in message content
                    ->orWhereExists(function($query) use ($searchTerm) {
                        $query->select(DB::raw(1))
                            ->from('multi_compare_messages as m')
                            ->whereColumn('m.conversation_id', 'c.id')
                            ->where('m.content', 'LIKE', "%{$searchTerm}%");
                    });
            })
            ->orderBy('c.updated_at', 'desc')
            ->select('c.*')
            ->groupBy('c.id') // ✅ CHANGED: Use groupBy instead of distinct
            ->get()
            ->map(function ($conv) {
                $conv->selected_models = json_decode($conv->selected_models, true) ?? [];
                return $conv;
            });
        
        return response()->json($conversations);
    }

    // Archive/Unarchive single conversation
    public function toggleArchiveMultiCompareConversation($hexCode)
    {
        $conversation = MultiCompareConversation::where('hex_code', $hexCode)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $conversation->update([
            'archived' => !$conversation->archived
        ]);

        return response()->json([
            'message' => $conversation->archived ? 'Conversation archived' : 'Conversation unarchived',
            'archived' => $conversation->archived
        ]);
    }

    // Bulk delete conversations
    public function bulkDeleteMultiCompareConversations(Request $request)
    {
        $request->validate([
            'hex_codes' => 'required|array',
            'hex_codes.*' => 'exists:multi_compare_conversations,hex_code'
        ]);

        $deleted = MultiCompareConversation::whereIn('hex_code', $request->hex_codes)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json([
            'message' => "{$deleted} conversation(s) deleted successfully",
            'deleted_count' => $deleted
        ]);
    }

    // Bulk archive/unarchive conversations
    public function bulkArchiveMultiCompareConversations(Request $request)
    {
        $request->validate([
            'hex_codes' => 'required|array',
            'hex_codes.*' => 'exists:multi_compare_conversations,hex_code',
            'archive' => 'required|boolean'
        ]);

        $updated = MultiCompareConversation::whereIn('hex_code', $request->hex_codes)
            ->where('user_id', auth()->id())
            ->update(['archived' => $request->archive]);

        $action = $request->archive ? 'archived' : 'unarchived';

        return response()->json([
            'message' => "{$updated} conversation(s) {$action} successfully",
            'updated_count' => $updated
        ]);
    }

    /**
     * Generate a shareable link for a conversation
     */
    public function generateShareLink(Request $request, $hexCode)
    {
        $request->validate([
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        $conversation = MultiCompareConversation::where('hex_code', $hexCode)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Check if there's already an active share
        $existingShare = $conversation->activeShare;

        if ($existingShare) {
            // Return existing share
            $shareUrl = route('shared.conversation', ['token' => $existingShare->share_token]);
            
            return response()->json([
                'success' => true,
                'share_token' => $existingShare->share_token,
                'share_url' => $shareUrl,
                'expires_at' => $existingShare->expires_at,
                'view_count' => $existingShare->view_count,
                'created_at' => $existingShare->created_at,
            ]);
        }

        // Create new share
        $expiresAt = null;
        if ($request->has('expires_in_days')) {
            $expiresAt = now()->addDays($request->expires_in_days);
        }

        $share = MultiCompareConversationShare::create([
            'conversation_id' => $conversation->id,
            'share_token' => MultiCompareConversationShare::generateToken(),
            'created_by' => auth()->id(),
            'is_public' => true,
            'expires_at' => $expiresAt,
        ]);

        $shareUrl = route('shared.conversation', ['token' => $share->share_token]);

        return response()->json([
            'success' => true,
            'share_token' => $share->share_token,
            'share_url' => $shareUrl,
            'expires_at' => $share->expires_at,
            'view_count' => 0,
            'created_at' => $share->created_at,
        ]);
    }

    /**
     * Revoke a share link
     */
    public function revokeShareLink($hexCode)
    {
        $conversation = MultiCompareConversation::where('hex_code', $hexCode)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $share = $conversation->activeShare;

        if ($share) {
            $share->update(['is_public' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Share link revoked successfully'
        ]);
    }

    /**
     * Get share information
     */
    public function getShareInfo($hexCode)
    {
        $conversation = MultiCompareConversation::where('hex_code', $hexCode)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $share = $conversation->activeShare;

        if (!$share) {
            return response()->json([
                'success' => false,
                'shared' => false,
            ]);
        }

        $shareUrl = route('shared.conversation', ['token' => $share->share_token]);

        return response()->json([
            'success' => true,
            'shared' => true,
            'share_token' => $share->share_token,
            'share_url' => $shareUrl,
            'expires_at' => $share->expires_at,
            'view_count' => $share->view_count,
            'created_at' => $share->created_at,
        ]);
    }

    /**
     * View a shared conversation (public access)
     */
    public function viewSharedConversation($token)
    {
        $share = MultiCompareConversationShare::where('share_token', $token)
            ->with(['conversation.messages.attachments'])
            ->firstOrFail();

        // Validate share
        if (!$share->isValid()) {
            abort(403, 'This shared link has expired or is no longer available.');
        }

        // Increment view count
        $share->incrementViews();

        $conversation = $share->conversation;

        // Get models for display
        $availableModels = \App\Models\AISettings::active()
            ->whereNotNull('openaimodel')
            ->whereNotNull('provider')
            ->select('openaimodel', 'displayname', 'cost_per_m_tokens', 'provider')
            ->orderBy('provider')
            ->orderBy('displayname')
            ->get()
            ->groupBy('provider');

        return view('backend.chattermate.shared-conversation', compact(
            'conversation',
            'share',
            'availableModels'
        ));
    }

    /**
     * Detect if message is an export request
     */
    private function detectFileExportRequest($message)
    {
        $message = strtolower(trim($message));
        
        Log::info('Checking for export request', ['message' => $message]);
        
        $fileFormats = [
            'csv' => 'csv',
            'excel' => 'xlsx',
            'xlsx' => 'xlsx',
            'spreadsheet' => 'xlsx',
            'pdf' => 'pdf',
            'word' => 'docx',
            'docx' => 'docx',
            'document' => 'docx',
            'text' => 'txt',
            'txt' => 'txt',
            'notepad' => 'txt',
            'powerpoint' => 'pptx',
            'ppt' => 'pptx',
            'presentation' => 'pptx',
            'slide' => 'pptx',
        ];
        
        // ✅ FIRST: Check if ANY format is mentioned
        $formatFound = null;
        foreach ($fileFormats as $keyword => $format) {
            if (str_contains($message, $keyword)) {
                $formatFound = $format;
                Log::info('Format detected', ['keyword' => $keyword, 'format' => $format]);
                break;
            }
        }
        
        // ✅ If no format mentioned, NOT an export request
        if (!$formatFound) {
            Log::info('No file format detected in message');
            return null;
        }
        
        // ✅ IMPROVED: Check for export intent words
        $exportWords = [
            'export',
            'download',
            'save',
            'convert',
            'generate',
        ];
        
        $hasExportWord = false;
        foreach ($exportWords as $word) {
            if (str_contains($message, $word)) {
                $hasExportWord = true;
                Log::info('Export word detected', ['word' => $word]);
                break;
            }
        }
        
        if (!$hasExportWord) {
            Log::info('No export intent word found');
            return null;
        }
        
        // ✅ NEW: Check for CREATION phrases (these override export intent)
        // These are requests to CREATE new content, not export existing
        $creationPhrases = [
            'write a',
            'write an',
            'create a',
            'create an',
            'make a',
            'make an',
            'build a',
            'build an',
            'design a',
            'design an',
            'show me a',
            'show me an',
            'give me a',
            'give me an',
            'provide a',
            'provide an',
        ];
        
        foreach ($creationPhrases as $phrase) {
            if (str_contains($message, $phrase)) {
                Log::info('Content creation phrase detected, not an export', [
                    'phrase' => $phrase
                ]);
                return null;
            }
        }
        
        // ✅ NEW: Specific patterns that indicate CREATION, not EXPORT
        // Example: "generate a pdf with a chart" = CREATE
        // Example: "export as pdf" = EXPORT
        $creationPatterns = [
            '/generate\s+(a|an)\s+.*?(pdf|docx|xlsx|csv|pptx)/i',
            '/create\s+(a|an)\s+.*?(pdf|docx|xlsx|csv|pptx)/i',
            '/make\s+(a|an)\s+.*?(pdf|docx|xlsx|csv|pptx)/i',
            '/write\s+(a|an)\s+.*?(pdf|docx|xlsx|csv|pptx)/i',
        ];
        
        foreach ($creationPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                Log::info('Creation pattern matched, not an export', [
                    'pattern' => $pattern
                ]);
                return null;
            }
        }
        
        // ✅ NEW: Strong export indicators
        $strongExportIndicators = [
            'export as',
            'export to',
            'export the',
            'export this',
            'export that',
            'download as',
            'download the',
            'download this',
            'download that',
            'save as',
            'save the',
            'save this',
            'save that',
            'convert to',
            'convert the',
            'convert this',
            'convert that',
        ];
        
        $hasStrongIndicator = false;
        foreach ($strongExportIndicators as $indicator) {
            if (str_contains($message, $indicator)) {
                $hasStrongIndicator = true;
                Log::info('Strong export indicator found', ['indicator' => $indicator]);
                break;
            }
        }
        
        // ✅ If we have a strong indicator, it's definitely an export
        if ($hasStrongIndicator) {
            Log::info('Export request CONFIRMED via strong indicator', ['format' => $formatFound]);
            return $formatFound;
        }
        
        // ✅ NEW: Check for content references (the code, the poem, the table, etc.)
        $contentReferences = [
            'the code',
            'the poem',
            'the song',
            'the table',
            'the list',
            'the data',
            'the content',
            'the response',
            'the message',
            'the output',
            'this code',
            'this poem',
            'this song',
            'this table',
            'that code',
            'that poem',
            'that song',
            'above',
            'previous',
        ];
        
        $hasContentReference = false;
        foreach ($contentReferences as $reference) {
            if (str_contains($message, $reference)) {
                $hasContentReference = true;
                Log::info('Content reference found', ['reference' => $reference]);
                break;
            }
        }
        
        // ✅ If we have an export word + content reference + format, it's an export
        if ($hasExportWord && $hasContentReference && $formatFound) {
            Log::info('Export request CONFIRMED via content reference', [
                'export_word' => true,
                'content_reference' => true,
                'format' => $formatFound
            ]);
            return $formatFound;
        }
        
        // ✅ Fallback: If we have export word + format but no clear creation intent, assume export
        if ($hasExportWord && $formatFound) {
            Log::info('Export request CONFIRMED via fallback logic', ['format' => $formatFound]);
            return $formatFound;
        }
        
        Log::info('No export intent detected, returning null');
        return null;
    }

    /**
     * Handle file export request - Generate separate files for each model
     */
    private function handleFileExportRequest($format, $conversationId, $selectedModels, $exportMessage = '')
    {
        try {
            Log::info('File export requested', [
                'format' => $format, 
                'conversation_id' => $conversationId,
                'selectedModels' => $selectedModels,
                'export_message' => $exportMessage
            ]);
            
            // Get conversation and messages
            $conversation = MultiCompareConversation::with('messages')->findOrFail($conversationId);
            
            // ✅ NEW: Detect what content to export
            $contentReference = $this->detectExportContentReference($exportMessage);
            
            Log::info('Export content reference', $contentReference);
            
            // ✅ NEW: Find the right message to export
            $targetMessage = $this->findMessageToExport($conversation, $contentReference);
            
            if (!$targetMessage) {
                return [
                    'success' => false,
                    'error' => 'No content found to export'
                ];
            }
            
            Log::info('Target message selected for export', [
                'message_id' => $targetMessage->id,
                'created_at' => $targetMessage->created_at
            ]);
            
            // Extract data from the selected message
            if (!$targetMessage->all_responses) {
                return [
                    'success' => false,
                    'error' => 'No content found in the selected message'
                ];
            }
            
            $allResponses = $targetMessage->all_responses;
            
            // ✅ Log what's available for debugging
            Log::info('Available responses in message', [
                'response_keys' => array_keys($allResponses),
                'count' => count($allResponses)
            ]);
            
            $exportResults = []; // ✅ Store results for each model
            
            // ✅ SPECIAL HANDLING FOR SMART_ALL MODE
            if (in_array('smart_all_auto', $selectedModels)) {
                Log::info('Smart All mode detected, exporting first available response');
                
                // Remove 'files' key if it exists
                unset($allResponses['files']);
                
                // Get the first actual model response (not 'export' or 'files')
                $actualResponses = array_filter($allResponses, function($key) {
                    return $key !== 'export' && $key !== 'files';
                }, ARRAY_FILTER_USE_KEY);
                
                if (empty($actualResponses)) {
                    return [
                        'success' => false,
                        'error' => 'No model responses found to export'
                    ];
                }
                
                // Get the first response
                $firstModelId = array_key_first($actualResponses);
                $modelContent = $actualResponses[$firstModelId];
                
                Log::info('Exporting content from model', [
                    'model' => $firstModelId,
                    'content_length' => strlen($modelContent)
                ]);
                
                // Process this single response
                $result = $this->processSingleExport($format, $modelContent, $conversation->title, 'smart_all_auto', $firstModelId);
                
                if ($result['success']) {
                    $exportResults['smart_all_auto'] = $result;
                }
                
            } else {
                // ✅ IMPROVED: Generate separate file for EACH model with better matching
                foreach ($selectedModels as $requestedModelId) {
                    $modelContent = null;
                    $actualModelUsed = null;
                    
                    Log::info('Looking for content for model', ['requested' => $requestedModelId]);
                    
                    // Strategy 1: Direct match
                    if (isset($allResponses[$requestedModelId])) {
                        $modelContent = $allResponses[$requestedModelId];
                        $actualModelUsed = $requestedModelId;
                        Log::info('✅ Strategy 1: Direct match found');
                    }
                    
                    // Strategy 2: Find by provider (for both Fixed and Smart Same modes)
                    if (!$modelContent) {
                        // Get provider from the requested model
                        $requestedModelSettings = \App\Models\AISettings::where('openaimodel', $requestedModelId)->first();
                        
                        if ($requestedModelSettings) {
                            $requestedProvider = $requestedModelSettings->provider;
                            Log::info('Strategy 2: Searching by provider', ['provider' => $requestedProvider]);
                            
                            // Look through all responses for a model from the same provider
                            foreach ($allResponses as $responseModelId => $response) {
                                if ($responseModelId === 'files' || $responseModelId === 'export') {
                                    continue;
                                }
                                
                                $responseModelSettings = \App\Models\AISettings::where('openaimodel', $responseModelId)->first();
                                
                                if ($responseModelSettings && $responseModelSettings->provider === $requestedProvider) {
                                    $modelContent = $response;
                                    $actualModelUsed = $responseModelId;
                                    Log::info('✅ Strategy 2: Found by provider match', [
                                        'requested' => $requestedModelId,
                                        'actual' => $responseModelId
                                    ]);
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Strategy 3: For Smart Same mode with _smart_panel suffix
                    if (!$modelContent && str_contains($requestedModelId, '_smart_panel')) {
                        $provider = str_replace('_smart_panel', '', $requestedModelId);
                        Log::info('Strategy 3: Smart panel mode', ['provider' => $provider]);
                        
                        foreach ($allResponses as $responseModelId => $response) {
                            if ($responseModelId === 'files' || $responseModelId === 'export') {
                                continue;
                            }
                            
                            $responseModelSettings = \App\Models\AISettings::where('openaimodel', $responseModelId)->first();
                            
                            if ($responseModelSettings && $responseModelSettings->provider === $provider) {
                                $modelContent = $response;
                                $actualModelUsed = $responseModelId;
                                Log::info('✅ Strategy 3: Found via smart panel', [
                                    'panel' => $requestedModelId,
                                    'actual' => $responseModelId
                                ]);
                                break;
                            }
                        }
                    }
                    
                    if (!$modelContent) {
                        Log::warning("❌ No content found for model after all strategies", [
                            'requested_model' => $requestedModelId,
                            'available_models' => array_keys($allResponses)
                        ]);
                        continue;
                    }
                    
                    Log::info('Processing export for model', [
                        'requested_model' => $requestedModelId,
                        'actual_model' => $actualModelUsed,
                        'content_length' => strlen($modelContent)
                    ]);
                    
                    // Process this model's export (use requested model ID for panel mapping)
                    $result = $this->processSingleExport($format, $modelContent, $conversation->title, $requestedModelId, $actualModelUsed);
                    
                    if ($result['success']) {
                        $exportResults[$requestedModelId] = $result;
                    }
                }
            }
            
            if (empty($exportResults)) {
                return [
                    'success' => false,
                    'error' => 'No content found to export for selected models'
                ];
            }
            
            return [
                'success' => true,
                'results' => $exportResults
            ];
            
        } catch (\Exception $e) {
            Log::error('File export error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to generate file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process a single model's export
     */
    private function processSingleExport($format, $modelContent, $conversationTitle, $panelModelId, $actualModelId)
    {
        try {
            // ✅ Add model identifier to content
            $contentWithHeader = "# Generated by: " . $actualModelId . "\n\n" . $modelContent;
            
            // Try to extract table data first
            $extractedData = $this->extractTableDataFromContent($contentWithHeader);
            
            $headers = null;
            $data = null;
            
            if ($extractedData) {
                // We have table data
                $headers = $extractedData['headers'];
                $data = $extractedData['data'];
            }
            
            // Generate file for this model
            $tempFile = null;
            $cleanModelId = str_replace(['_smart_panel', '/', ':', '.'], ['', '_', '_', '_'], $panelModelId);
            $fileName = 'chattermate_' . $cleanModelId . '_export_' . date('Y-m-d_His');
            
            switch ($format) {
                case 'csv':
                    if (!$extractedData) {
                        return $this->exportPlainText($format, $contentWithHeader, $conversationTitle . ' - ' . $actualModelId);
                    }
                    $tempFile = $this->generateCSVFile($data, $headers);
                    $fileName .= '.csv';
                    break;
                    
                case 'xlsx':
                    if (!$extractedData) {
                        return $this->exportPlainText($format, $contentWithHeader, $conversationTitle . ' - ' . $actualModelId);
                    }
                    $tempFile = $this->generateExcelFile($data, $headers);
                    $fileName .= '.xlsx';
                    break;
                    
                case 'pdf':
                    // ✅ IMPROVED: Pass raw content to PDF generator for proper formatting
                    $tempFile = $this->generatePDFFile($data, $headers, $contentWithHeader);
                    $fileName .= '.pdf';
                    break;
                    
                case 'docx':
                    if (!$extractedData) {
                        return $this->exportPlainText($format, $contentWithHeader, $conversationTitle . ' - ' . $actualModelId);
                    }
                    $tempFile = $this->generateDOCXFile($data, $headers);
                    $fileName .= '.docx';
                    break;
                    
                case 'txt':
                    if ($extractedData) {
                        $tempFile = $this->generateTXTFileFromTable($data, $headers);
                    } else {
                        return $this->exportPlainText($format, $contentWithHeader, $conversationTitle . ' - ' . $actualModelId);
                    }
                    $fileName .= '.txt';
                    break;
                    
                case 'pptx':
                    if ($extractedData) {
                        $tempFile = $this->generatePPTXFileFromTable($data, $headers, $conversationTitle . ' - ' . $actualModelId);
                    } else {
                        $tempFile = $this->generatePPTXFileFromText($contentWithHeader, $conversationTitle . ' - ' . $actualModelId);
                    }
                    $fileName .= '.pptx';
                    break;
                    
                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported format: ' . $format
                    ];
            }
            
            if (!$tempFile || !file_exists($tempFile)) {
                Log::error("Failed to generate file for model: {$panelModelId}");
                return [
                    'success' => false,
                    'error' => 'Failed to generate file'
                ];
            }
            
            // Upload to Azure
            $downloadUrl = $this->uploadFileToAzure($tempFile, $fileName, $format);
            
            // Clean up temp file
            unlink($tempFile);
            
            // ✅ Return result for this model
            return [
                'success' => true,
                'download_url' => $downloadUrl,
                'file_name' => $fileName,
                'file_format' => strtoupper($format),
                'rows' => isset($data) ? count($data) : 0,
                'columns' => isset($headers) ? count($headers) : 0,
                'model' => $panelModelId,
                'actual_model' => $actualModelId
            ];
            
        } catch (\Exception $e) {
            Log::error('Single export error: ' . $e->getMessage(), [
                'panel_model' => $panelModelId,
                'actual_model' => $actualModelId
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to process export: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Extract table data from content
     */
    private function extractTableDataFromContent($content)
    {
        // Try markdown tables first
        if (preg_match('/\|(.+)\|/', $content)) {
            $lines = explode("\n", $content);
            $headers = [];
            $tableData = [];
            $inTable = false;
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;
                
                // Check if line is a table row
                if (preg_match('/^\|(.+)\|$/', $line)) {
                    // Remove outer pipes and split
                    $cells = array_map('trim', explode('|', trim($line, '|')));
                    
                    // Skip separator lines
                    if (preg_match('/^[\s\-:|\s]+$/', $line)) {
                        $inTable = true;
                        continue;
                    }
                    
                    if (empty($headers)) {
                        // First row is headers
                        $headers = $cells;
                    } elseif ($inTable) {
                        // Data row
                        $tableData[] = array_combine($headers, array_slice($cells, 0, count($headers)));
                    }
                }
            }
            
            if (!empty($headers) && !empty($tableData)) {
                return ['headers' => $headers, 'data' => $tableData];
            }
        }
        
        // Try JSON arrays
        if (preg_match('/```json\s*(\[[\s\S]*?\])\s*```/', $content, $matches)) {
            try {
                $jsonData = json_decode($matches[1], true);
                if (is_array($jsonData) && !empty($jsonData) && is_array($jsonData[0])) {
                    $headers = array_keys($jsonData[0]);
                    return ['headers' => $headers, 'data' => $jsonData];
                }
            } catch (\Exception $e) {
                // Continue to next method
            }
        }
        
        return null;
    }

    /**
     * Export plain text content
     */
    private function exportPlainText($format, $content, $title)
    {
        $fileName = 'chattermate_export_' . date('Y-m-d_His');
        $parsedown = new Parsedown();
        
        try {
            switch ($format) {
                case 'pdf':
                    // Generate PDF from markdown
                    $html = $parsedown->text($content);
                    $pdf = new TCPDF();
                    $pdf->AddPage();
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->writeHTML($html, true, false, true, false, '');
                    
                    $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.pdf';
                    $pdf->Output($tempFile, 'F');
                    $fileName .= '.pdf';
                    break;
                    
                case 'docx':
                    // Generate DOCX from markdown
                    $phpWord = new PhpWord();
                    $section = $phpWord->addSection();
                    
                    // Add title
                    $section->addTitle($title, 1);
                    $section->addTextBreak(1);
                    
                    // Add content (simple paragraph)
                    $section->addText($content);
                    
                    $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.docx';
                    $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                    $objWriter->save($tempFile);
                    $fileName .= '.docx';
                    break;
                    
                case 'xlsx':
                    // Generate Excel with content
                    $spreadsheet = new Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setCellValue('A1', 'ChatterMate Export');
                    $sheet->setCellValue('A2', $title);
                    $sheet->setCellValue('A4', $content);
                    
                    $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';
                    $writer = new Xlsx($spreadsheet);
                    $writer->save($tempFile);
                    $fileName .= '.xlsx';
                    break;
                    
                case 'txt':
                    // Simple text file
                    $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.txt';
                    file_put_contents($tempFile, "ChatterMate Export\n" . $title . "\n\n" . $content);
                    $fileName .= '.txt';
                    break;
                    
                case 'pptx':
                    // Generate PPTX from markdown content
                    // Since this is plain text content (not table), create a slide with text box
                    $tempFile = $this->generatePPTXFileFromText($content, $title);
                    $fileName .= '.pptx';
                    break;
                    
                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported format for plain text export'
                    ];
            }
            
            // Upload to Azure
            $downloadUrl = $this->uploadFileToAzure($tempFile, $fileName, $format);
            unlink($tempFile);
            
            return [
                'success' => true,
                'download_url' => $downloadUrl,
                'file_name' => $fileName,
                'file_format' => strtoupper($format),
                'rows' => 1,
                'columns' => 1
            ];
            
        } catch (\Exception $e) {
            Log::error('Plain text export error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to generate file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate CSV file from table data
     */
    private function generateCSVFile($data, $headers)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.csv';
        
        $csv = Writer::createFromPath($tempFile, 'w+');
        $csv->setDelimiter(',');
        $csv->setEnclosure('"');
        $csv->setOutputBOM(Writer::BOM_UTF8);
        
        // Write title row
        $csv->insertOne(['ChatterMate AI Export']);
        $csv->insertOne(['Generated: ' . date('Y-m-d H:i:s')]);
        $csv->insertOne([]); // Empty row
        
        // Write headers
        $csv->insertOne($headers);
        
        // Write data rows
        foreach ($data as $row) {
            if (is_array($row)) {
                if (!empty($headers) && array_keys($row) !== range(0, count($row) - 1)) {
                    $csvRow = [];
                    foreach ($headers as $header) {
                        $csvRow[] = $row[$header] ?? '';
                    }
                    $csv->insertOne($csvRow);
                } else {
                    $csv->insertOne(array_values($row));
                }
            }
        }
        
        return $tempFile;
    }

    /**
     * Generate Excel file from table data
     */
    private function generateExcelFile($data, $headers)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ChatterMate Export');
        
        // Title section (rows 1-3)
        $sheet->setCellValue('A1', 'ChatterMate AI Export');
        $sheet->mergeCells('A1:' . chr(64 + count($headers)) . '1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        
        $sheet->setCellValue('A2', 'Generated: ' . date('F j, Y \a\t g:i A'));
        $sheet->mergeCells('A2:' . chr(64 + count($headers)) . '2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'size' => 10,
                'color' => ['rgb' => '6B7280'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        
        $sheet->setCellValue('A3', 'Total Rows: ' . count($data));
        $sheet->mergeCells('A3:' . chr(64 + count($headers)) . '3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'size' => 10,
                'italic' => true,
                'color' => ['rgb' => '6B7280'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
        
        // Header row (row 5)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $sheet->getStyle($col . '5')->applyFromArray($headerStyle);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        
        // Data rows (starting from row 6)
        $row = 6;
        foreach ($data as $dataRow) {
            $col = 'A';
            foreach ($headers as $header) {
                $value = is_array($dataRow) ? ($dataRow[$header] ?? '') : $dataRow;
                $sheet->setCellValue($col . $row, $value);
                
                // Apply data cell styling
                $sheet->getStyle($col . $row)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_TOP,
                        'wrapText' => true,
                    ],
                ]);
                
                $col++;
            }
            
            // Alternating row colors
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':' . chr(64 + count($headers)) . $row)
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F9FAFB');
            }
            
            $row++;
        }
        
        // Freeze header row
        $sheet->freezePane('A6');
        
        // Add footer
        $sheet->getHeaderFooter()->setOddFooter('&L&B' . 'ChatterMate AI' . '&RPage &P of &N');
        
        $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return $tempFile;
    }

    /**
     * Generate TXT file from table data
     */
    private function generateTXTFileFromTable($data, $headers)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.txt';
        $content = "ChatterMate Export - Table Data\n\n";
        
        // Add headers
        $content .= implode(" | ", $headers) . "\n";
        $content .= str_repeat("-", 50) . "\n";
        
        // Add data
        foreach ($data as $row) {
            $rowValues = [];
            foreach ($headers as $header) {
                $rowValues[] = $row[$header] ?? '';
            }
            $content .= implode(" | ", $rowValues) . "\n";
        }
        
        file_put_contents($tempFile, $content);
        return $tempFile;
    }

    /**
     * Generate PPTX file from Text
     * Uses PHPPresentation if available, otherwise fallback or error
     */
    private function generatePPTXFileFromText($content, $title)
    {
        // Check if class exists (assuming phpoffice/phppresentation might not be installed)
        if (!class_exists('\PhpOffice\PhpPresentation\PhpPresentation')) {
            return $this->createBasicPPTX($content, $title);
        }
        
        // If library exists:
        $objPHPPowerPoint = new \PhpOffice\PhpPresentation\PhpPresentation();
        $currentSlide = $objPHPPowerPoint->getActiveSlide();

        // Create a shape (text)
        $shape = $currentSlide->createRichTextShape()
            ->setHeight(600)
            ->setWidth(900)
            ->setOffsetX(10)
            ->setOffsetY(10);
        $shape->getActiveParagraph()->getAlignment()->setHorizontal( \PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_CENTER );
        $textRun = $shape->createTextRun($title);
        $textRun->getFont()->setBold(true)->setSize(28)->setColor( new \PhpOffice\PhpPresentation\Style\Color( 'FF000000' ) );

        // Content
        $shape2 = $currentSlide->createRichTextShape()
            ->setHeight(600)
            ->setWidth(900)
            ->setOffsetX(10)
            ->setOffsetY(100);
        $shape2->createTextRun(substr($content, 0, 1000)); // Limit text per slide

        $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.pptx';
        $oWriterPPTX = \PhpOffice\PhpPresentation\IOFactory::createWriter($objPHPPowerPoint, 'PowerPoint2007');
        $oWriterPPTX->save($tempFile);
        
        return $tempFile;
    }

    /**
     * Generate PPTX file from Table
     */
    private function generatePPTXFileFromTable($data, $headers, $title)
    {
        // Reuse text logic for now but format as table if library exists
        $content = "Table Data:\n" . implode(" | ", $headers) . "\n...\n(See extracted data)";
        return $this->generatePPTXFileFromText($content, $title);
    }

    /**
     * Minimal PPTX Generator (No Library Fallback)
     */
    private function createBasicPPTX($content, $title)
    {
        // Minimal error message if library is missing, as manual PPTX creation is too complex for this scope
        throw new \Exception("PPTX Export requires 'phpoffice/phppresentation'. Please install it via composer.");
    }

    /**
     * Generate PDF file from table data
     */
    private function generatePDFFile($data, $headers, $rawContent = null)
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Document information
        $pdf->SetCreator('ChatterMate AI');
        $pdf->SetAuthor('ChatterMate AI');
        $pdf->SetTitle('ChatterMate Export - ' . date('Y-m-d'));
        $pdf->SetSubject('Data Export');
        $pdf->SetKeywords('ChatterMate, AI, Export, Data');
        
        // Custom header
        $pdf->SetHeaderData('', 0, 'ChatterMate AI Export', 'Generated: ' . date('F j, Y \a\t g:i A'));
        
        // Header and footer fonts
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', 11]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', 8]);
        
        // Margins
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // Auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 25);
        
        // Add page
        $pdf->AddPage();
        
        // ✅ Enhanced CSS for better formatting
        $styles = '
        <style>
            body {
                font-family: helvetica;
                font-size: 11px;
                line-height: 1.6;
                color: #333333;
            }
            h1 {
                color: #4F46E5;
                font-size: 24px;
                font-weight: bold;
                margin-top: 20px;
                margin-bottom: 15px;
                padding-bottom: 8px;
                border-bottom: 3px solid #4F46E5;
            }
            h2 {
                color: #6366F1;
                font-size: 20px;
                font-weight: bold;
                margin-top: 18px;
                margin-bottom: 12px;
                padding-bottom: 5px;
                border-bottom: 2px solid #E0E7FF;
            }
            h3 {
                color: #818CF8;
                font-size: 16px;
                font-weight: bold;
                margin-top: 15px;
                margin-bottom: 10px;
            }
            h4, h5, h6 {
                color: #A5B4FC;
                font-size: 14px;
                font-weight: bold;
                margin-top: 12px;
                margin-bottom: 8px;
            }
            p {
                margin-top: 5px;
                margin-bottom: 10px;
                text-align: justify;
                white-space: pre-wrap;
            }
            strong, b {
                font-weight: bold;
                color: #1F2937;
            }
            em, i {
                font-style: italic;
                color: #4B5563;
            }
            ul, ol {
                margin-left: 20px;
                margin-top: 8px;
                margin-bottom: 12px;
            }
            li {
                margin-bottom: 5px;
                line-height: 1.5;
            }
            code {
                background-color: #F3F4F6;
                color: #DC2626;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: courier;
                font-size: 10px;
            }
            pre {
                background-color: #1F2937;
                color: #F9FAFB;
                padding: 15px;
                border-radius: 8px;
                margin: 15px 0;
                overflow-x: auto;
                font-family: courier;
                font-size: 9px;
                line-height: 1.4;
                border-left: 4px solid #4F46E5;
                white-space: pre-wrap;
            }
            pre code {
                background-color: transparent;
                color: #F9FAFB;
                padding: 0;
            }
            blockquote {
                border-left: 4px solid #4F46E5;
                padding-left: 15px;
                margin: 15px 0;
                color: #6B7280;
                font-style: italic;
                background-color: #F9FAFB;
                padding: 10px 15px;
            }
            table {
                border-collapse: collapse;
                width: 100%;
                margin: 15px 0;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            th {
                background-color: #4F46E5;
                color: #FFFFFF;
                font-weight: bold;
                padding: 12px 10px;
                text-align: left;
                border: 1px solid #3730A3;
                font-size: 11px;
            }
            td {
                padding: 10px;
                border: 1px solid #E5E7EB;
                font-size: 10px;
                vertical-align: top;
            }
            tr:nth-child(even) {
                background-color: #F9FAFB;
            }
            tr:nth-child(odd) {
                background-color: #FFFFFF;
            }
            tr:hover {
                background-color: #F3F4F6;
            }
            hr {
                border: none;
                border-top: 2px solid #E5E7EB;
                margin: 20px 0;
            }
            a {
                color: #4F46E5;
                text-decoration: underline;
            }
            .highlight {
                background-color: #FEF3C7;
                padding: 2px 4px;
                border-radius: 3px;
            }
        </style>';
        
        // ✅ If we have raw markdown content, convert it
        if ($rawContent) {
            // ✅ CLEAN VERSION: No placeholders needed
            // Parse markdown to HTML with line breaks enabled
            $parsedown = new \Parsedown();
            $parsedown->setBreaksEnabled(true); // ✅ This preserves line breaks
            $html = $parsedown->text($rawContent);
            
            // Apply styles and write
            $pdf->SetFont('helvetica', '', 11);
            $pdf->writeHTML($styles . $html, true, false, true, false, '');
            
        } else if (!empty($data) && !empty($headers)) {
            // ✅ Table mode with enhanced styling
            $pdf->SetFont('helvetica', '', 9);
            
            $tableHtml = $styles . '<table>';
            
            // Headers
            $tableHtml .= '<thead><tr>';
            foreach ($headers as $header) {
                $tableHtml .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $tableHtml .= '</tr></thead><tbody>';
            
            // Data rows
            foreach ($data as $row) {
                $tableHtml .= '<tr>';
                foreach ($headers as $header) {
                    $value = is_array($row) ? ($row[$header] ?? '') : $row;
                    // ✅ Preserve line breaks in table cells too
                    $value = nl2br(htmlspecialchars($value));
                    $tableHtml .= '<td>' . $value . '</td>';
                }
                $tableHtml .= '</tr>';
            }
            
            $tableHtml .= '</tbody></table>';
            
            $pdf->writeHTML($tableHtml, true, false, true, false, '');
        }
        
        $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.pdf';
        $pdf->Output($tempFile, 'F');
        
        return $tempFile;
    }

    /**
     * Generate DOCX file from table data
     */
    private function generateDOCXFile($data, $headers)
    {
        $phpWord = new PhpWord();
        
        // Set document properties
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('ChatterMate AI');
        $properties->setCompany('ChatterMate');
        $properties->setTitle('ChatterMate Export');
        $properties->setDescription('Generated by ChatterMate AI');
        $properties->setCategory('Data Export');
        $properties->setCreated(time());
        
        $section = $phpWord->addSection([
            'marginLeft' => 1000,
            'marginRight' => 1000,
            'marginTop' => 1000,
            'marginBottom' => 1000,
        ]);
        
        // Add title
        $section->addText(
            'ChatterMate AI Export',
            [
                'name' => 'Arial',
                'size' => 18,
                'bold' => true,
                'color' => '4F46E5'
            ],
            [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 200
            ]
        );
        
        // Add metadata
        $section->addText(
            'Generated: ' . date('F j, Y \a\t g:i A'),
            [
                'name' => 'Arial',
                'size' => 10,
                'color' => '6B7280'
            ],
            [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 100
            ]
        );
        
        $section->addText(
            'Total Rows: ' . count($data),
            [
                'name' => 'Arial',
                'size' => 10,
                'italic' => true,
                'color' => '6B7280'
            ],
            [
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
                'spaceAfter' => 300
            ]
        );
        
        // Create table
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => 'E5E7EB',
            'cellMargin' => 80,
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ];
        
        $headerStyle = [
            'bold' => true,
            'color' => 'FFFFFF',
            'size' => 11,
            'name' => 'Arial'
        ];
        
        $headerCellStyle = [
            'bgColor' => '4F46E5',
            'valign' => 'center'
        ];
        
        $cellStyle = [
            'valign' => 'top'
        ];
        
        $table = $section->addTable($tableStyle);
        
        // Add header row
        $table->addRow(400);
        foreach ($headers as $header) {
            $cell = $table->addCell(2000, $headerCellStyle);
            $cell->addText(htmlspecialchars($header), $headerStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        }
        
        // Add data rows
        foreach ($data as $index => $row) {
            $table->addRow();
            
            // Alternating row colors
            $rowCellStyle = $cellStyle;
            if ($index % 2 == 0) {
                $rowCellStyle['bgColor'] = 'F9FAFB';
            }
            
            foreach ($headers as $header) {
                $value = is_array($row) ? ($row[$header] ?? '') : $row;
                $cell = $table->addCell(2000, $rowCellStyle);
                $cell->addText(
                    htmlspecialchars($value),
                    ['name' => 'Arial', 'size' => 10]
                );
            }
        }
        
        // Add footer
        $footer = $section->addFooter();
        $footer->addPreserveText(
            'ChatterMate AI | Page {PAGE} of {NUMPAGES}',
            ['size' => 9, 'color' => '6B7280'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        
        $tempFile = tempnam(sys_get_temp_dir(), 'export_') . '.docx';
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        return $tempFile;
    }

    /**
     * Upload file to Azure Blob Storage
     */
    private function uploadFileToAzure($tempFile, $fileName, $format)
    {
        try {
            $blobClient = BlobRestProxy::createBlobService(config('filesystems.disks.azure.connection_string'));
            $containerName = config('filesystems.disks.azure.container');
            
            $azureFileName = 'chattermate-exports/' . $fileName;
            $fileContent = file_get_contents($tempFile);
            
            // Set content type based on format
            $contentTypes = [
                'csv' => 'text/csv',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            $options = new CreateBlockBlobOptions();
            $options->setContentType($contentTypes[$format] ?? 'application/octet-stream');
            
            $blobClient->createBlockBlob($containerName, $azureFileName, $fileContent, $options);
            
            $baseUrl = rtrim(config('filesystems.disks.azure.url'), '/');
            return "{$baseUrl}/{$containerName}/{$azureFileName}";
            
        } catch (\Exception $e) {
            Log::error('Azure upload failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload table CSV (for inline table exports)
     */
    public function uploadTableCSV(Request $request)
    {
        try {
            $file = $request->file('csv_file');
            
            if (!$file) {
                return response()->json(['success' => false, 'error' => 'No file uploaded'], 400);
            }
            
            $fileName = 'table_' . time() . '.csv';
            $azureFileName = 'chattermate-exports/' . $fileName;
            
            $blobClient = BlobRestProxy::createBlobService(config('filesystems.disks.azure.connection_string'));
            $containerName = config('filesystems.disks.azure.container');
            
            $options = new CreateBlockBlobOptions();
            $options->setContentType('text/csv');
            
            $blobClient->createBlockBlob(
                $containerName, 
                $azureFileName, 
                file_get_contents($file->getRealPath()),
                $options
            );
            
            $baseUrl = rtrim(config('filesystems.disks.azure.url'), '/');
            $downloadUrl = "{$baseUrl}/{$containerName}/{$azureFileName}";
            
            return response()->json([
                'success' => true,
                'download_url' => $downloadUrl,
                'file_name' => $fileName
            ]);
            
        } catch (\Exception $e) {
            Log::error('Table CSV upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export table inline (from frontend table data)
     */
    public function exportTableInline(Request $request)
    {
        try {
            $request->validate([
                'format' => 'required|in:csv,xlsx,pdf,docx',
                'headers' => 'required|array',
                'data' => 'required|array',
            ]);
            
            $format = $request->input('format');
            $headers = $request->input('headers');
            $data = $request->input('data');
            
            // Generate file
            $tempFile = null;
            $fileName = 'table_export_' . date('Y-m-d_His');
            
            switch ($format) {
                case 'csv':
                    $tempFile = $this->generateCSVFile($data, $headers);
                    $fileName .= '.csv';
                    break;
                    
                case 'xlsx':
                    $tempFile = $this->generateExcelFile($data, $headers);
                    $fileName .= '.xlsx';
                    break;
                    
                case 'pdf':
                    $tempFile = $this->generatePDFFile($data, $headers);
                    $fileName .= '.pdf';
                    break;
                    
                case 'docx':
                    $tempFile = $this->generateDOCXFile($data, $headers);
                    $fileName .= '.docx';
                    break;
            }
            
            // Upload to Azure
            $downloadUrl = $this->uploadFileToAzure($tempFile, $fileName, $format);
            
            // Clean up
            unlink($tempFile);
            
            return response()->json([
                'success' => true,
                'download_url' => $downloadUrl,
                'file_name' => $fileName
            ]);
            
        } catch (\Exception $e) {
            Log::error('Inline table export error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detect WHAT content to export from the request
     */
    private function detectExportContentReference($message)
    {
        $message = strtolower(trim($message));
        
        // Content type keywords
        $contentTypes = [
            'code' => ['code', 'script', 'program', 'function', 'algorithm'],
            'poem' => ['poem', 'poetry', 'verse'],
            'song' => ['song', 'lyrics', 'music'],
            'table' => ['table', 'data', 'chart', 'spreadsheet'],
            'list' => ['list', 'items', 'points'],
            'story' => ['story', 'narrative', 'tale'],
            'essay' => ['essay', 'article', 'writing'],
        ];
        
        // Position references
        $positions = [
            'this' => 0,      // Current/last message
            'that' => 0,      // Current/last message
            'above' => 0,     // Message right before export request
            'previous' => 0,  // Message right before export request
            'first' => 'first',
            'second' => 'second',
            'last' => 0,
        ];
        
        $result = [
            'content_type' => null,
            'position' => 0,  // 0 = last matching message, 'first' = first matching, 'second' = second matching
        ];
        
        // Check for content type references
        foreach ($contentTypes as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($message, $keyword)) {
                    $result['content_type'] = $type;
                    Log::info("Content type detected: {$type}");
                    break 2;
                }
            }
        }
        
        // Check for position references
        foreach ($positions as $keyword => $position) {
            if (str_contains($message, $keyword)) {
                $result['position'] = $position;
                Log::info("Position reference detected: {$keyword}");
                break;
            }
        }
        
        return $result;
    }

    /**
     * Identify content type of a message
     */
    private function identifyContentType($content)
    {
        $types = [];
        
        // Check for code blocks
        if (preg_match('/```[\s\S]*?```/', $content) || 
            preg_match('/\b(function|class|def|const|var|let|if|else|for|while)\b/', $content)) {
            $types[] = 'code';
        }
        
        // Check for tables
        if (preg_match('/\|(.+)\|/', $content)) {
            $types[] = 'table';
        }
        
        // Check for poem structure (short lines, potential rhyme)
        $lines = explode("\n", $content);
        $shortLines = array_filter($lines, fn($line) => strlen(trim($line)) > 0 && strlen(trim($line)) < 60);
        if (count($shortLines) > 4 && count($shortLines) / max(count($lines), 1) > 0.5) {
            $types[] = 'poem';
        }
        
        // Check for song/lyrics (verse, chorus, etc.)
        if (preg_match('/\b(verse|chorus|bridge|refrain|intro|outro)\b/i', $content)) {
            $types[] = 'song';
        }
        
        // Check for lists
        if (preg_match('/^[\s]*[-*•]\s+/m', $content) || preg_match('/^\d+\.\s+/m', $content)) {
            $types[] = 'list';
        }
        
        return $types;
    }

    /**
     * Find the right message to export based on user's request
     */
    private function findMessageToExport($conversation, $contentReference, $currentMessageId = null)
    {
        // Get all assistant messages (excluding the current export request)
        $messages = $conversation->messages()
            ->where('role', 'assistant')
            ->where('id', '!=', $currentMessageId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        if ($messages->isEmpty()) {
            return null;
        }
        
        // If no specific content type requested, return the last message
        if (!$contentReference['content_type']) {
            Log::info("No content type specified, using last message");
            return $messages->first();
        }
        
        $targetType = $contentReference['content_type'];
        $position = $contentReference['position'];
        
        Log::info("Searching for content", [
            'type' => $targetType,
            'position' => $position,
            'total_messages' => $messages->count()
        ]);
        
        // Find messages with matching content type
        $matchingMessages = [];
        foreach ($messages as $message) {
            $content = '';
            
            // Get content from all_responses
            if ($message->all_responses) {
                // Remove 'files' key if exists
                $responses = $message->all_responses;
                unset($responses['files']);
                
                // Get first actual response
                $actualResponses = array_filter($responses, function($key) {
                    return $key !== 'export' && $key !== 'files';
                }, ARRAY_FILTER_USE_KEY);
                
                if (!empty($actualResponses)) {
                    $content = reset($actualResponses);
                }
            } else {
                $content = $message->content ?? '';
            }
            
            // Identify content types in this message
            $messageTypes = $this->identifyContentType($content);
            
            Log::info("Message analysis", [
                'message_id' => $message->id,
                'detected_types' => $messageTypes,
                'content_preview' => substr($content, 0, 100)
            ]);
            
            // Check if this message contains the target type
            if (in_array($targetType, $messageTypes)) {
                $matchingMessages[] = $message;
            }
        }
        
        if (empty($matchingMessages)) {
            Log::warning("No messages found with content type: {$targetType}, falling back to last message");
            return $messages->first();
        }
        
        // Handle position
        if ($position === 'first') {
            $selected = end($matchingMessages); // Last in array = first chronologically
            Log::info("Selected first {$targetType} message", ['id' => $selected->id]);
            return $selected;
        } else if ($position === 'second' && count($matchingMessages) >= 2) {
            $selected = $matchingMessages[count($matchingMessages) - 2];
            Log::info("Selected second {$targetType} message", ['id' => $selected->id]);
            return $selected;
        } else {
            $selected = $matchingMessages[0]; // First in array = last chronologically
            Log::info("Selected last {$targetType} message", ['id' => $selected->id]);
            return $selected;
        }
    }


}