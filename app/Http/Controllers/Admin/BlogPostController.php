<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogPostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Media;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function index(): View
    {
        $posts = BlogPost::query()->with('category')->latest('published_at')->paginate(20);

        return view('admin.blog-posts.index', ['posts' => $posts]);
    }

    public function create(): View
    {
        return $this->form(new BlogPost);
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $data = $this->prepared($request);
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        $post = BlogPost::create($data);
        $this->syncFeaturedImage($post, $request->integer('featured_media_id') ?: null);

        return redirect()->route('admin.blog-posts.index')->with('status', 'مقاله ایجاد شد.');
    }

    public function edit(BlogPost $blogPost): View
    {
        return $this->form($blogPost);
    }

    public function update(StoreBlogPostRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $data = $this->prepared($request);

        if ($data['status'] === 'published' && ! $blogPost->published_at) {
            $data['published_at'] = now();
        } elseif ($data['status'] === 'draft') {
            $data['published_at'] = null;
        } else {
            unset($data['published_at']);
        }

        $blogPost->update($data);
        $this->syncFeaturedImage($blogPost, $request->integer('featured_media_id') ?: null);

        return redirect()->route('admin.blog-posts.index')->with('status', 'مقاله به‌روزرسانی شد.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $blogPost->delete();

        return redirect()->route('admin.blog-posts.index')->with('status', 'مقاله حذف شد.');
    }

    private function form(BlogPost $post): View
    {
        return view('admin.blog-posts.form', [
            'post' => $post,
            'categories' => BlogCategory::query()->orderBy('name')->get(),
            'media' => Media::query()->latest()->limit(60)->get(),
            'currentFeaturedId' => $post->exists ? $post->featuredImage->first()?->id : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepared(StoreBlogPostRequest $request): array
    {
        $data = $request->safe()->except('featured_media_id');
        $data['slug'] = ($data['slug'] ?? null) ?: PersianSlug::make($data['title']);

        return $data;
    }

    private function syncFeaturedImage(BlogPost $post, ?int $mediaId): void
    {
        $post->featuredImage()->detach();

        if ($mediaId) {
            $post->featuredImage()->attach($mediaId, ['role' => 'featured', 'sort_order' => 0]);
        }
    }
}
