<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Redirect to main site login since authentication is shared
        if ($request->expectsJson()) {
            return null;
        }

        // For local testing: redirect to main site login
        // In production, users will already be logged in from main site
        $mainSiteUrl = env('MAIN_SITE_URL', 'http://localhost:8000');
        return $mainSiteUrl . '/login';
    }
}
