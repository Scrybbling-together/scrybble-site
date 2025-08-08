<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function index()
    {

    }

    public function download(string $path)
    {
        return Storage::disk('storage-decrypted')->download($path);
    }
}
