<?php

declare(strict_types=1);

use App\Http\Controllers\ConnectedGumroadLicenseController;
use App\Http\Controllers\DebugBundleController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InspectSyncController;
use App\Http\Controllers\OnboardingStateController;
use App\Http\Controllers\OnetimecodeController;
use App\Http\Controllers\ReMarkableDocumentFeedbackController;
use App\Http\Controllers\ResetReMarkableConnectionController;
use App\Http\Controllers\RMFiletreeController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\SyncUserController;
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

Route::prefix('debug-bundle')->middleware(['web'])->group(function () {
    Route::get('{sync:sync_id}/input', [DebugBundleController::class, 'input']);
    Route::get('{sync:sync_id}/full', [DebugBundleController::class, 'full']);
});

Route::group(['middleware' => ['auth:api', 'throttle:180,1']], routes: static function () {
    Route::get('prmdownload/{path}', [DownloadController::class, 'download'])
        ->middleware('auth:api')
        ->where('path', '.*')
        ->name('prmdownload');

    Route::post('sync/file', [FileController::class, 'show']);
    Route::post('sync/status', [SyncController::class, 'show']);

    Route::post('sync/remarkable-document-share', [ReMarkableDocumentFeedbackController::class, 'store']);

    Route::get('sync/delta', [SyncController::class, 'index']);
    Route::get('sync/onboardingState', OnboardingStateController::class);
    Route::post('sync/RMFileTree', [RMFiletreeController::class, 'index']);
    Route::post('sync/search', [RMFiletreeController::class, 'search']);
    Route::get('sync/inspect-sync', [InspectSyncController::class, 'index']);

    Route::post('sync/gumroadLicense', [ConnectedGumroadLicenseController::class, 'store']);
    Route::post('sync/onetimecode', [OnetimecodeController::class, 'create']);
    Route::delete('sync/remarkable-connection', [ResetReMarkableConnectionController::class, 'destroy']);

    Route::get('/sync/user', SyncUserController::class);
});
