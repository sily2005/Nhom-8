<?php

use App\Http\Controllers\GatewayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Gateway Catch-All Reverse Proxy Routes
|--------------------------------------------------------------------------
*/

Route::any('{path?}', [GatewayController::class, 'handle'])
    ->where('path', '.*');
