<?php
declare(strict_types=1);

use App\Http\Controllers\ConnectedGumroadLicenseController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InspectSyncController;
use App\Http\Controllers\OnboardingStateController;
use App\Http\Controllers\OnetimecodeController;
use App\Http\Controllers\ReMarkableDocumentFeedbackController;
use App\Http\Controllers\RMFiletreeController;
use App\Http\Controllers\SyncController;
use App\Http\Middleware\HandleCryptFS;
use App\Models\Sync;
use App\Services\GumroadService;
use App\Services\OnboardingStateService;
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

Route::group(['middleware' => ["auth:api", HandleCryptFS::class, "throttle:180,1"]], routes: static function () {
    Route::group(['middleware' => HandleCryptFS::class], static function () {
        Route::post('sync/file', [FileController::class, 'show']);
        Route::post('sync/status', [SyncController::class, 'show']);

        Route::post('sync/remarkable-document-share', [ReMarkableDocumentFeedbackController::class, 'store']);

        Route::get('sync/delta', [SyncController::class, 'index']);
        Route::get('sync/onboardingState', OnboardingStateController::class);
        Route::post('sync/RMFileTree', [RMFiletreeController::class, 'index']);
        Route::get('sync/inspect-sync', [InspectSyncController::class, 'index']);
    });

    Route::post('sync/gumroadLicense', [ConnectedGumroadLicenseController::class, "store"]);
    Route::post('sync/onetimecode', [OnetimecodeController::class, 'create']);

    Route::get('/sync/user', function (Request $request, GumroadService $gumroadService, OnboardingStateService $onboardingStateService) {
        $user = $request->user();
        return [
            'user' => $user,
            'subscription_status' => $gumroadService->licenseInfo(),
            'total_syncs' => Sync::forUser($user)->count(),
            'onboarding_state' => $onboardingStateService->getState()
        ];
    });
});
