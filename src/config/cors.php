<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | O front (Vue) roda em outra origem e fala com a API via token Bearer,
    | então não há cookies envolvidos (supports_credentials = false).
    |
    | - FRONTEND_URL: origens exatas liberadas (o front publicado na Vercel);
    |   várias podem ser separadas por vírgula.
    | - CORS_ALLOW_LOCALHOST: libera localhost/127.0.0.1 em qualquer porta,
    |   para desenvolver o front sem mexer no .env da API.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FRONTEND_URL', 'https://univesp-tv-front.vercel.app')),
    ))),

    'allowed_origins_patterns' => filter_var(env('CORS_ALLOW_LOCALHOST', false), FILTER_VALIDATE_BOOLEAN)
        ? ['#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#']
        : [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
