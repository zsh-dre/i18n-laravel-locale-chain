<?php

// Example route demonstrating locale fallback chains.
// Add this to your routes/web.php to test:

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

Route::get('/locale-chain-test', function () {
    // Set locale to pt-BR
    App::setLocale('pt-BR');

    return response()->json([
        'locale' => App::getLocale(),
        'fallback' => App::getFallbackLocale(),

        // PHP file translations (lang/xx/messages.php)
        'php_greeting' => __('messages.greeting'),

        // JSON translations (lang/xx.json) — this is the bug fix!
        'json_welcome' => __('Welcome'),
    ]);
});
