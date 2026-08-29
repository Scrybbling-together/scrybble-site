<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Stephenjude\FilamentBlog\Models\Post;

class NewsController extends Controller
{
    public function index(): View
    {
        $query = Post::with(['author', 'category'])
            ->published()
            ->whereHas('category', fn ($q) => $q->where('name', 'News'))
            ->orderBy('published_at', 'desc');

        $posts = $query->paginate(8);

        return view('pages.news.index', [
            'posts' => $posts,
        ]);
    }

    public function show($slug): View
    {
        $post = Post::with(['author', 'category'])
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Get related posts (same category, excluding current post)
        $relatedPosts = Post::with(['author', 'category'])
            ->where('blog_category_id', $post->blog_category_id)
            ->where('id', '!=', $post->id)
            ->published()
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();

        return view('pages.news.show', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
        ]);
    }
}
