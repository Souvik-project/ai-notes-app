<?php

use App\Http\Controllers\Api\NoteController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function (): void {
    Route::get('/notes/search', [NoteController::class, 'semanticSearch']);
    Route::post('/notes/{note}/summary', [NoteController::class, 'summary']);
    Route::apiResource('notes', NoteController::class);
});
