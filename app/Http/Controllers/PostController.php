<?php

namespace App\Http\Controllers;

use App\Models\Post;

class PostController extends Controller {
    public function list()
    {
        return response()->json(Post::select(["title", "slug"])->get());
    }

    public function show(string $slug)
    {
        $post = Post::firstWhere('slug', $slug);

        return response()->json($post);
    }
}
