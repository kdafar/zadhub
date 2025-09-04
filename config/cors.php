<?php

return [
    'paths' => ['api/*', 'admin', 'login'], // Added 'admin' and 'login' to ensure these paths are covered
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3010',
        'https://zad-hub.com',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // Changed from false to true
];
