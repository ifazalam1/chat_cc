<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file is used by the openai-php/laravel package.
    | We simply read the API key from the existing OPENAI_API_KEY env var
    | that your app already uses in config/services.php.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),

    // You can override the default base URL or organization if needed:
    // 'organization' => env('OPENAI_ORGANIZATION'),
    // 'base_uri' => env('OPENAI_BASE_URI', 'https://api.openai.com/v1'),
];


