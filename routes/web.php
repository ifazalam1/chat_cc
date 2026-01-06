<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompareController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Multi-Model Compare Routes
|--------------------------------------------------------------------------
|
| All routes for the multi-model AI comparison subdomain.
| Sessions are shared with main site via database sessions.
|
*/

// Authentication routes (for local testing only - remove in production)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('/');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
});

Route::post('/logout', function (Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Protected routes (require authentication)
Route::middleware(['auth'])->group(function () {

    // Main compare interface (aliased for compatibility)
    Route::get('/{hexCode?}', [CompareController::class, 'index'])->name('chat.compare');

    // Chat endpoint
    Route::post('/chat-compare', [CompareController::class, 'multiModelChat'])->name('chat.multi-compare');

    // Conversation management
    Route::get('/get-multi-compare-chats', [CompareController::class, 'getMultiCompareChats'])->name('get-multi-compare-chats');
    Route::get('/get-multi-compare-conversation/{hexCode}', [CompareController::class, 'getMultiCompareConversation'])->name('get-multi-compare-conversation');
    Route::delete('/delete-multi-compare-conversation/{hexCode}', [CompareController::class, 'deleteMultiCompareConversation'])->name('delete-multi-compare-conversation');
    Route::put('/update-multi-compare-conversation-title/{hexCode}', [CompareController::class, 'updateMultiCompareConversationTitle'])->name('update-multi-compare-conversation-title');
    Route::put('/toggle-archive-multi-compare-conversation/{hexCode}', [CompareController::class, 'toggleArchiveMultiCompareConversation'])->name('toggle-archive-multi-compare-conversation');
    Route::post('/bulk-delete-multi-compare-conversations', [CompareController::class, 'bulkDeleteMultiCompareConversations'])->name('bulk-delete-multi-compare-conversations');
    Route::post('/bulk-archive-multi-compare-conversations', [CompareController::class, 'bulkArchiveMultiCompareConversations'])->name('bulk-archive-multi-compare-conversations');
    Route::post('/search-multi-compare-conversations', [CompareController::class, 'searchMultiCompareConversations'])->name('search-multi-compare-conversations');

    // Sharing
    Route::post('/multi-compare-conversation/{hexCode}/share', [CompareController::class, 'generateShareLink'])->name('generate-share-link');
    Route::delete('/multi-compare-conversation/{hexCode}/share', [CompareController::class, 'revokeShareLink'])->name('revoke-share-link');
    Route::get('/multi-compare-conversation/{hexCode}/share-info', [CompareController::class, 'getShareInfo'])->name('get-share-info');

    // Export
    Route::post('/export-multi-chat-conversation/{hexCode}', [CompareController::class, 'exportMultiChatConversation'])->name('export-multi-chat-conversation');
    Route::post('/export-table-inline', [CompareController::class, 'exportTableInline'])->name('export-table-inline');
    Route::post('/upload-table-csv', [CompareController::class, 'uploadTableCSV'])->name('upload-table-csv');
    Route::get('/download-export/{filename}', [CompareController::class, 'downloadExport'])->name('download-export');

    // Translation
    Route::post('/translate-text', [CompareController::class, 'translateText'])->name('translate.text');

    // Redirects to main site for routes that don't exist in compare-chat
    Route::get('/pricing', function () {
        return redirect(env('MAIN_SITE_URL', 'http://localhost:8000') . '/pricing');
    })->name('pricing');

    Route::get('/dashboard', function () {
        return redirect(env('MAIN_SITE_URL', 'http://localhost:8000') . '/dashboard');
    })->name('dashboard');

    Route::get('/profile/edit', function () {
        return redirect(env('MAIN_SITE_URL', 'http://localhost:8000') . '/profile/edit');
    })->name('edit.profile');

    Route::get('/ai/image/gallery', function () {
        return redirect(env('MAIN_SITE_URL', 'http://localhost:8000') . '/ai/image/gallery');
    })->name('ai.image.gallery');
});

// Public routes
Route::get('/shared/conversation/{token}', [CompareController::class, 'viewSharedConversation'])->name('shared.conversation');
