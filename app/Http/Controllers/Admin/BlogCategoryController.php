<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogCategoryRequest;
use App\Models\BlogCategory;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BlogCategoryController extends Controller
{
    public function index(): View
    {
        $categories = BlogCategory::query()->withCount('posts')->orderBy('name')->paginate(20);

        return view('admin.blog-categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('admin.blog-categories.form', ['category' => new BlogCategory]);
    }

    public function store(StoreBlogCategoryRequest $request): RedirectResponse
    {
        BlogCategory::create($this->prepared($request));

        return redirect()->route('admin.blog-categories.index')->with('status', 'دسته‌بندی ایجاد شد.');
    }

    public function edit(BlogCategory $blogCategory): View
    {
        return view('admin.blog-categories.form', ['category' => $blogCategory]);
    }

    public function update(StoreBlogCategoryRequest $request, BlogCategory $blogCategory): RedirectResponse
    {
        $blogCategory->update($this->prepared($request));

        return redirect()->route('admin.blog-categories.index')->with('status', 'دسته‌بندی به‌روزرسانی شد.');
    }

    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        if ($blogCategory->posts()->exists()) {
            return redirect()->route('admin.blog-categories.index')
                ->with('status', 'این دسته دارای مقاله است و قابل حذف نیست.');
        }

        $blogCategory->delete();

        return redirect()->route('admin.blog-categories.index')->with('status', 'دسته‌بندی حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function prepared(StoreBlogCategoryRequest $request): array
    {
        $data = $request->validated();
        $data['slug'] = ($data['slug'] ?? null) ?: PersianSlug::make($data['name']);

        return $data;
    }
}
