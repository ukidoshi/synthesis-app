<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SynthesisStageApiController;
use App\Http\Controllers\Api\ChemistAuthController;

Route::post('/chemists/register', [ChemistAuthController::class, 'register']);
Route::post('/chemists/login', [ChemistAuthController::class, 'login']);

Route::get('/synthesis-stages', [SynthesisStageApiController::class, 'index']);
Route::get('/synthesis-stages/feed/{id?}', [SynthesisStageApiController::class, 'feed']);

Route::middleware('auth:api')->group(function () {
    Route::post('/chemists/logout', [ChemistAuthController::class, 'logout']);

    Route::get('/synthesis-stages/draft', [SynthesisStageApiController::class, 'draft']);
    Route::post('/synthesis-stages', [SynthesisStageApiController::class, 'store']);
    Route::put('/synthesis-stages/{id}/publish', [SynthesisStageApiController::class, 'publish']);
    Route::delete('/synthesis-stages/{id}', [SynthesisStageApiController::class, 'destroy']);
    Route::post('/synthesis-stages/{id}/like', [SynthesisStageApiController::class, 'toggleLike']);
});
