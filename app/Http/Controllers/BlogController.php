<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('pages.blog.index');
    }

    public function show(string $slug): View
    {
        $posts = [
            'understanding-remarkable-coordinate-system' => 'pages.blog.understanding-remarkable-coordinate-system',
        ];

        abort_if(!array_key_exists($slug, $posts), 404);

        return view($posts[$slug]);
    }
}
