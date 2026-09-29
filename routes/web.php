<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SynthesisStageController;

Route::get('/', fn() => redirect('/synthesis-stages'));

// GET запросы
Route::get('/synthesis-stages', [SynthesisStageController::class, 'renderStageGrid'])->name('synthesis-stages.grid');
Route::get('/synthesis-stages/draft', [SynthesisStageController::class, 'renderStageDraft'])->name('synthesis-stages.draft');
Route::get('/synthesis-stages/feed/{synthesisStageId?}', [SynthesisStageController::class, 'renderStageFeed'])->name('synthesis-stages.feed');

// POST запросы
Route::post('/synthesis-stages/draft', [SynthesisStageController::class, 'storeStageDraft']);
Route::post('/synthesis-stages/{synthesisStageId}/publish', [SynthesisStageController::class, 'publishStage']);
Route::post('/synthesis-stages/{synthesisStageId}/delete', [SynthesisStageController::class, 'destroyStageDirectSql']);
