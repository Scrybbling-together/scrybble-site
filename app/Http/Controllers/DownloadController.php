<?php

namespace App\Http\Controllers;

use App\Helpers\UserStorage;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index() {}

    public function download(Request $request, string $path)
    {
        $storage = UserStorage::get($request->user());

        return $storage->download($path);
    }
}
