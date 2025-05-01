<?php

use App\Http\Controllers\ClientSecretController;
use App\Http\Controllers\ConnectedGumroadLicenseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\SharedDocumentsController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\GumroadLicenseInformationController;
use App\Http\Controllers\GumroadSaleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InspectSyncController;
use App\Http\Controllers\OnboardingStateController;
use App\Http\Controllers\OnetimecodeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RemarkableDocumentShareController;
use App\Http\Controllers\RMFiletreeController;
use App\Http\Controllers\SentryTunnelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [HomeController::class, 'index']);

Route::middleware(['middleware' => 'deployment.self-hosted'])->get('/client-secret', [ClientSecretController::class, "show"]);
Route::middleware(['middleware' => 'auth:sanctum'])->get('/sanctum/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth']], static function () {
    Route::get('/app/', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/connect-license', [ConnectedGumroadLicenseController::class, 'store'])->name('connect-license');
});

// required by Fortify: https://github.com/laravel/fortify/issues/155#issuecomment-732531717
Route::get('/base/reset-password/{token}', [HomeController::class, 'index'])->name('password.reset');

// required by sentry:
// https://docs.sentry.io/platforms/javascript/guides/react/troubleshooting/#dealing-with-ad-blockers
Route::post("/tunnel", [SentryTunnelController::class, "index"]);

Route::get('prmdownload/{path}', [DownloadController::class, "download"])->where('path', '.*')->name('prmdownload');

Route::group(['middleware' => ['auth'], 'prefix' => "api"], static function () {
    Route::get('onboardingState', OnboardingStateController::class);
    Route::get('licenseInformation', GumroadLicenseInformationController::class);

    Route::post('gumroadLicense', [ConnectedGumroadLicenseController::class, "store"]);

    Route::post('/onetimecode', [OnetimecodeController::class, 'create']);

    Route::post('/file', [FileController::class, 'show'])->name('download');

    Route::get('inspect-sync', [InspectSyncController::class, "index"]);

    Route::post('RMFileTree', [RMFiletreeController::class, 'index']);

    Route::post('remarkable-document-share', [RemarkableDocumentShareController::class, 'store']);
});

Route::group(['prefix' => 'api'], static function () {
    Route::get('gumroadSale/{sale_id}', [GumroadSaleController::class, "show"]);

    Route::get("posts", [PostController::class, "list"]);
    Route::get("posts/{slug}", [PostController::class, "show"]);
});


Route::get('shared_documents', [SharedDocumentsController::class, 'index']);
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::fallback([HomeController::class, 'index']);
