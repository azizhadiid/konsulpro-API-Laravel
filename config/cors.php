<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'midtrans-notification'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'supports_credentials' => true,
];
