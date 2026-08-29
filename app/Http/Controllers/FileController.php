<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataClasses\SyncContext;
use App\Jobs\DownloadRemarkableFileJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    /**
     * Request a file to be synced.
     *
     * Accepts either:
     * - Legacy: { file: "/path/to/file" }
     * - New: { rmFileId: "uuid", name: "filename" }
     *
     * When both are provided, rmFileId takes precedence.
     */
    public function show(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'file' => 'nullable|string',
            'rmFileId' => 'nullable|string',
            'name' => 'nullable|string|required_with:rmFileId',
        ]);

        $rmFileId = $validated['rmFileId'] ?? null;
        $name = $validated['name'] ?? null;
        $file = $validated['file'] ?? null;

        // Require either file OR (rmFileId + name)
        if (! $rmFileId && ! $file) {
            return response()->json([
                'message' => 'Either file or rmFileId with name is required.',
                'errors' => [
                    'file' => ['Either file or rmFileId with name is required.'],
                ],
            ], 422);
        }

        // Prefer rmFileId if both are provided
        if ($rmFileId) {
            $input_filename = $name;
            $rm_file_id = $rmFileId;
        } else {
            $input_filename = $file;
            $rm_file_id = null;
        }

        $sync_context = new SyncContext($input_filename, $user, $rm_file_id);
        Log::info("user=`$user` requested file file=`$input_filename` rmFileId=`$rm_file_id`; Dispatching `DownloadRemarkableFileJob`");
        DownloadRemarkableFileJob::dispatch($sync_context);

        return response()->json([
            'sync_id' => $sync_context->sync->id,
            'filename' => $sync_context->input_filename,
        ]);
    }
}
