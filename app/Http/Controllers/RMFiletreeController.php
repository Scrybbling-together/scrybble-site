<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\Sync;
use App\Services\RMapi;
use Eloquent\Pathogen\AbsolutePath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RMFiletreeController extends Controller
{
    public function search(SearchRequest $request, RMapi $rmapi): JsonResponse
    {
        $files = $rmapi->find(
            $request->input('query'),
            $request->input('starred'),
            $request->input('tags', [])
        );

        $syncs = Sync::syncMetadataForFiles($files->pluck('path'));

        $filesWithSync = $files->map(fn ($file) => [
            ...$file,
            'sync' => $syncs[$file['path']] ?? null,
        ]);

        return response()->json([
            'items' => $filesWithSync,
        ]);
    }

    public function index(Request $request, RMapi $rmapi): JsonResponse
    {
        $path = $request->get('path') ?? '/';

        $filesAndFolders = $rmapi->list($path);
        if ($path !== '/') {
            $parent = AbsolutePath::fromString($path)->parent()->normalize()->string();
            if (! Str::endsWith($parent, '/')) {
                $parent .= '/';
            }
            $filesAndFolders->prepend(['type' => 'd', 'name' => '..', 'path' => $parent]);
        }

        $filePaths = $filesAndFolders->filter(fn ($item) => $item['type'] === 'f')->pluck('path');
        $syncs = Sync::syncMetadataForFiles($filePaths);

        $filesAndFolders = $filesAndFolders->map(fn ($fileOrFolder) => [
            ...$fileOrFolder,
            'sync' => $fileOrFolder['type'] === 'f' ? ($syncs[$fileOrFolder['path']] ?? null) : null,
        ]);

        return response()->json([
            'items' => $filesAndFolders,
            'cwd' => $path,
        ]);
    }
}
