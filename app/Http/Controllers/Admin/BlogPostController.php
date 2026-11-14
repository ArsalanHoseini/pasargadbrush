<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogPostRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Media;
use App\Models\Tag;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $posts = BlogPost::query()
            ->with('category')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($request->filled('category'), fn ($query) => $query->where('blog_category_id', $request->integer('category')))
            ->when(in_array($request->query('status'), ['published', 'draft'], true), fn ($query) => $query->where('status', $request->query('status')))
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.blog-posts.index', [
            'posts' => $posts,
            'categoryOptions' => BlogCategory::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return $this->form(new BlogPost);
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $data = $this->prepared($request);
        $data['published_at'] = $data['status'] === 'published' ? ($data['published_at'] ?? now()) : null;

        $post = BlogPost::create($data);
        $this->syncFeaturedImage($post, $request->integer('featured_media_id') ?: null);
        $this->syncTags($post, $request->string('tags')->toString());

        return redirect()->route('admin.blog-posts.index')->with('status', 'مقاله ایجاد شد.');
    }

    public function edit(BlogPost $blogPost): View
    {
        return $this->form($blogPost);
    }

    public function update(StoreBlogPostRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $data = $this->prepared($request);

        if ($data['status'] === 'published') {
            $data['published_at'] = $data['published_at'] ?? $blogPost->published_at ?? now();
        } else {
            $data['published_at'] = null;
        }

        $blogPost->update($data);
        $this->syncFeaturedImage($blogPost, $request->integer('featured_media_id') ?: null);
        $this->syncTags($blogPost, $request->string('tags')->toString());

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
            'currentTags' => $post->exists ? $post->tags->pluck('name')->implode('، ') : '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepared(StoreBlogPostRequest $request): array
    {
        $data = $request->safe()->except(['featured_media_id', 'tags']);
        $data['slug'] = ($data['slug'] ?? null) ?: PersianSlug::make($data['title']);
        $data['featured'] = $request->boolean('featured');

        return $data;
    }

    private function syncTags(BlogPost $post, string $tagsInput): void
    {
        $names = array_filter(array_map('trim', preg_split('/[،,]/u', $tagsInput) ?: []));

        $tagIds = collect($names)->map(function (string $name) {
            return Tag::firstOrCreate(
                ['slug' => PersianSlug::make($name)],
                ['name' => $name],
            )->id;
        });

        $post->tags()->sync($tagIds);
    }

    private function syncFeaturedImage(BlogPost $post, ?int $mediaId): void
    {
        $post->featuredImage()->detach();

        if ($mediaId) {
            $post->featuredImage()->attach($mediaId, ['role' => 'featured', 'sort_order' => 0]);
        }
    }
}
