<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Sync;
use App\Services\DownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

class SyncController extends Controller
{
    public function index(DownloadService $downloadService): JsonResponse
    {
        $user = Auth::user();

        $results =
            Sync::forUser($user)
                ->whereIsCompleted()
                ->get(['filename', 'sync_id', 'id'])
                ->map(function (Sync $sync) use ($downloadService, $user) {
                    try {
                        $url = $downloadService->prepareProcessedRemarksZipUrl($user->id, $sync->sync_id);
                    } catch (GoneHttpException) {
                        return null;
                    }

                    return [
                        'download_url' => $url,
                        'filename' => $sync->filename,
                        'id' => $sync->id,
                    ];
                })->filter(fn ($syncOrNull) => ! is_null($syncOrNull))->values()->toArray();

        return response()->json($results);
    }

    public function show(Request $request, DownloadService $downloadService)
    {
        $user = Auth::user();

        $sync_id = $request->integer('sync_id');
        $sync = Sync::select(['filename', 'created_at', 'completed', 'id', 'sync_id'])
            ->forUser(Auth::user())
            ->find($sync_id);

        $res = [
            'filename' => $sync->filename,
            'id' => $sync->id,
            'completed' => $sync->completed,
            'error' => $sync->hasError(),
        ];

        if ($sync->completed) {
            $res['download_url'] = $downloadService->prepareProcessedRemarksZipUrl($user->id, $sync->sync_id);
        }

        return response()->json($res);
    }
}
