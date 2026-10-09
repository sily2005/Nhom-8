<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services & Microservices
    |--------------------------------------------------------------------------
    */

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    ],

    'microservices' => [
        'auth' => env('AUTH_SERVICE_URL', 'http://127.0.0.1:8001'),
        'catalog' => env('CATALOG_SERVICE_URL', 'http://127.0.0.1:8002'),
        'order' => env('ORDER_SERVICE_URL', 'http://127.0.0.1:8003'),
    ],

];
