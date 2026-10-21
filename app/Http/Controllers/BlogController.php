<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()
            ->with(['featuredImage', 'category'])
            ->where('status', 'published')
            ->latest('published_at')
            ->paginate(12);

        return view('blog.index', ['posts' => $posts]);
    }

    public function show(BlogPost $post): View
    {
        abort_unless($post->status === 'published', 404);

        $related = BlogPost::query()
            ->with('featuredImage')
            ->where('status', 'published')
            ->where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->limit(3)
            ->get();

        return view('blog.show', [
            'post' => $post->load(['featuredImage', 'category']),
            'related' => $related,
        ]);
    }
}
