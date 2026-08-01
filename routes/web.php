<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ConnectedGumroadLicenseController;
use App\Http\Controllers\CustomHostInformationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProfileController;
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
Route::get('/support', fn () => view('pages.support'));
Route::get('/about', fn () => view('pages.about'));
Route::get('/roadmap', fn () => view('pages.roadmap'));

Route::get('/dashboard', fn () => view('pages.dashboard-replaced'));
Route::get('/purchased', fn () => view('pages.purchased'));

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

/**
 * Legal
 */
Route::get('/privacy-policy', fn () => view('pages.legal.privacy-policy'));

Route::middleware(['middleware' => 'deployment.self-hosted'])->get('/self-host-setup', [CustomHostInformationController::class, "show"]);
Route::middleware(['middleware' => 'auth:sanctum'])->get('/sanctum/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => ['auth']], static function () {
    Route::post('/connect-license', [ConnectedGumroadLicenseController::class, 'store'])->name('connect-license');
});

Route::group(['middleware' => 'auth'], static function () {
    Route::get('profile', ProfileController::class);
});

// TODO: Temporarily disabled because the underlying logic was removed for security review
//Route::get('shared_documents', [SharedDocumentsController::class, 'index']);
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::redirect('/app/', '/home');
