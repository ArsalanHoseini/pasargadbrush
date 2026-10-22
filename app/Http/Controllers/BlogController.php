<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Tag;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $featured = BlogPost::query()
            ->with(['featuredImage', 'category'])
            ->published()
            ->where('featured', true)
            ->latest('published_at')
            ->first();

        $posts = BlogPost::query()
            ->with(['featuredImage', 'category', 'tags'])
            ->published()
            ->when($featured, fn ($query) => $query->where('id', '!=', $featured->id))
            ->latest('published_at')
            ->paginate(12);

        return view('blog.index', ['posts' => $posts, 'featured' => $featured]);
    }

    public function tag(Tag $tag): View
    {
        $posts = $tag->posts()
            ->with(['featuredImage', 'category', 'tags'])
            ->published()
            ->latest('published_at')
            ->paginate(12);

        return view('blog.index', ['posts' => $posts, 'featured' => null, 'tag' => $tag]);
    }

    public function show(BlogPost $post): View
    {
        abort_unless($post->status === 'published' && $post->published_at?->lte(now()), 404);

        $post->increment('view_count');

        $related = BlogPost::query()
            ->with('featuredImage')
            ->published()
            ->where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->limit(3)
            ->get();

        return view('blog.show', [
            'post' => $post->load(['featuredImage', 'category', 'tags', 'approvedComments']),
            'related' => $related,
        ]);
    }

    public function feed(): Response
    {
        $posts = BlogPost::query()
            ->published()
            ->latest('published_at')
            ->limit(20)
            ->get();

        return response()
            ->view('blog.feed', ['posts' => $posts])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
