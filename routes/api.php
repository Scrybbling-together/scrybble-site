<?php
declare(strict_types=1);

use App\Http\Controllers\InspectSyncController;
use App\Http\Controllers\OnboardingStateController;
use App\Http\Controllers\RMFiletreeController;
use App\Http\Controllers\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => "auth:api"], static function () {
    Route::get('sync/delta', [SyncController::class, 'index']);
    Route::get('sync/onboardingState', OnboardingStateController::class);
    Route::post('sync/RMFileTree', [RMFiletreeController::class, 'index']);
    Route::get('sync/inspect-sync', [InspectSyncController::class, 'index']);
});
