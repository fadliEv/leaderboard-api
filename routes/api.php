<?php

use App\Http\Controllers\AssessmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/leaderboard', [LeaderboardController::class, 'getLeaderboard']);
Route::post('/submit-history', [LeaderboardController::class, 'submitHistoryScore']);
Route::post('/assessment', [AssessmentController::class, 'sendAssessment']);