<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'order-service',
        'status' => 'running',
    ]);
});
