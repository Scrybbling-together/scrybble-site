<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\UserStorage;
use App\Models\Sync;
use App\Models\SyncLog;
use App\Services\OnboardingStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetReMarkableConnectionController extends Controller
{
    public function destroy(Request $request, OnboardingStateService $onboardingStateService): JsonResponse
    {
        $user = $request->user();
        $storage = UserStorage::get($user);

        // Delete database records in a transaction
        DB::transaction(function () use ($user) {
            SyncLog::whereIn('sync_id', Sync::forUser($user)->pluck('id'))->delete();
            Sync::forUser($user)->delete();
        });

        // Clear the entire user storage directory
        foreach ($storage->directories('') as $directory) {
            $storage->deleteDirectory($directory);
        }

        foreach ($storage->files('') as $file) {
            $storage->delete($file);
        }

        Log::info('Reset reMarkable connection for user', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'newState' => $onboardingStateService->getState(),
        ]);
    }
}
