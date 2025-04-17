<?php

namespace App\Http\Controllers;

use App\Models\RemarkableDocumentShare;
use App\Services\DownloadService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

class SharedDocumentsController extends Controller
{
    public function index(DownloadService $downloadService)
    {
        $user = Auth::user();
        $shared = RemarkableDocumentShare::with("sync:id,filename,created_at,sync_id,user_id")->where('developer_access_consent_granted', true)->select([
            "id",
            "sync_id",
            "user_id",
            "feedback",
            "developer_access_consent_granted",
            "open_access_consent_granted"
        ])->with("user:id,email")->get();

        if ($user?->id !== 1) {
           $shared = $shared->filter(fn ($item) => $item["open_access_consent_granted"]);
        }

        return view("admin.sharedDocuments", [
            'shared' => $shared->map(function ($shared) use ($downloadService) {
                $public_sync_id = $shared->sync->sync_id;

                $output_href = null;
                if ($public_sync_id) {
                    try {
                        $output_href = $downloadService->prepareProcessedRemarksZipUrl($shared['user_id'], $public_sync_id);
                    } catch (GoneHttpException) {}
                }

                $input_href = null;
                if ($public_sync_id) {
                    try {
                        $input_href = $downloadService->prepareRMNZipUrl($shared['user_id'], $public_sync_id);
                    } catch (GoneHttpException) {}
                }

                return [
                    'id' => $public_sync_id,
                    'user_email' => $shared->user->email,
                    'created_at' => $shared->sync->created_at->diffForHumans(),
                    'feedback' => $shared->feedback,
                    'output_href' => $output_href,
                    'input_href' => $input_href,
                    'filename' => $shared->sync->filename,
                ];
            })
        ]);
    }
}
