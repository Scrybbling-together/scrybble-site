<?php

namespace App\Http\Controllers;

use App\Services\RMapi;
use Eloquent\Pathogen\AbsolutePath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RMFiletreeController extends Controller {

    public function index(Request $request, RMapi $rmapi): JsonResponse {
        $path = $request->get('path') ?? '/';

        $items = $rmapi->list($path);
        if ($path !== "/") {
            $parent = AbsolutePath::fromString($path)->parent()->normalize()->string();
            $items->prepend(['type' => 'd', 'name' => '..', 'path' => $parent]);
        }

        return response()->json([
            "items" => $items,
            "cwd" => $path
        ]);
    }
}
