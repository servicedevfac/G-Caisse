<?php
return [
    'name' => env('APP_NAME', 'CaisseFlow'), 'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false), 'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'), 'locale' => 'fr', 'fallback_locale' => 'en',
    'faker_locale' => 'fr_FR', 'cipher' => 'AES-256-CBC', 'key' => env('APP_KEY'),
];
