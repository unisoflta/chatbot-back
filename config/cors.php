<?php

return [
    'paths' => ['api/*','broadcasting/auth'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:5173'],
    'allowed_headers' => ['*'],
    'supports_credentials' => false, // muy importante para tokens
];
