<?php

namespace App\Http\Controllers;

use App\Models\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InspectSyncController extends Controller {
    public function index(Request $request) {
        $paginated = $request->boolean('paginated');
        $perPage = $request->integer('per_page', 10);

        $query = Sync::select(['filename', 'created_at', 'completed', 'id'])
            ->forUser(Auth::user())
            ->orderByDesc("created_at");

        if ($paginated) {
            $result = $query->paginate($perPage);

            return response()->json([
                'data' => collect($result->items())->map($this->formatSyncItem(...)),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total()
            ]);
        } else {
            $collection = $query->limit(10)
                ->get()
                ->map($this->formatSyncItem(...));

            return response()->json($collection);
        }
    }

    /**
     * Format a sync item for the API response
     *
     * @param Sync $syncItem
     * @return array
     */
    private function formatSyncItem(Sync $syncItem): array
    {
        return [
            'id' => $syncItem->id,
            'filename' => $syncItem->filename,
            'created_at' => $syncItem->created_at->diffForHumans(),
            'completed' => $syncItem->completed,
            'error' => !$syncItem->completed && ($syncItem->isOld() || $syncItem->hasError())
        ];
    }
}
