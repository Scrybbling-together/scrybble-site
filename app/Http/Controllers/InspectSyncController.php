<?php

namespace App\Http\Controllers;

use App\Models\Sync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InspectSyncController extends Controller
{
    public function index(Request $request)
    {
        $paginated = $request->boolean('paginated');
        $perPage = $request->integer('per_page', 10);

        $query = Sync::select(['filename', 'created_at', 'completed', 'id'])
            ->forUser(Auth::user())
            ->orderByDesc('created_at');

        if ($paginated) {
            $result = $query->paginate($perPage);

            return response()->json([
                'data' => collect($result->items())->map(Sync::formatForResponse(...)),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'total' => $result->total(),
            ]);
        } else {
            $collection = $query->limit(10)
                ->get()
                ->map(Sync::formatForResponse(...));

            return response()->json($collection);
        }
    }
}
