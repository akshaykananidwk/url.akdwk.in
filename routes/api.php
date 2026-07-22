<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API v1 — authenticated with API keys (Bearer sk_...).
| Rate limits are enforced per key/plan in the apikey middleware.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('apikey')->group(function () {
    Route::get('/me', [ApiController::class, 'me']);

    Route::get('/links', [ApiController::class, 'listLinks']);
    Route::post('/links', [ApiController::class, 'createLink']);
    Route::get('/links/{link}', [ApiController::class, 'showLink']);
    Route::put('/links/{link}', [ApiController::class, 'updateLink']);
    Route::delete('/links/{link}', [ApiController::class, 'deleteLink']);

    Route::get('/links/{link}/stats', [ApiController::class, 'linkStats']);
    Route::get('/links/{link}/qr', [ApiController::class, 'qr']);

    Route::get('/spaces', [ApiController::class, 'listSpaces']);
    Route::get('/domains', [ApiController::class, 'listDomains']);
});
