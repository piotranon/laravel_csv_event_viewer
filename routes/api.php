<?php

use App\Http\Controllers\EventAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::get('/events', [EventAnalyticsController::class, 'events']);
Route::get('/utm-ranking', [EventAnalyticsController::class, 'utmRanking']);
Route::post('/events/upload-csv', [EventAnalyticsController::class, 'uploadCsv']);
Route::post('/events/reset-csv', [EventAnalyticsController::class, 'resetCsv']);
