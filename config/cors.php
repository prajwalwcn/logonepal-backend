<?php

return [
    'paths' => ['api/*', 'storage/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL', 'http://localhost:3100'),
        'http://localhost:3100',
        'http://127.0.0.1:3100',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
    ])),

    // Dev convenience: any localhost port is accepted outside production.
    'allowed_origins_patterns' => env('APP_ENV') === 'local'
        ? ['#^http://(localhost|127\.0\.0\.1):\d+$#']
        : [],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['ETag'],

    'max_age' => 3600,

    'supports_credentials' => true,
];
