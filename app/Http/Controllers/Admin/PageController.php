<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePageRequest;
use App\Models\Page;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $pages = Page::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")))
            ->orderBy('title')
            ->paginate(20)
            ->withQueryString();

        return view('admin.pages.index', ['pages' => $pages]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page]);
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $data = $this->prepared($request);
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        Page::create($data);

        return redirect()->route('admin.pages.index')->with('status', 'صفحه ایجاد شد.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', ['page' => $page]);
    }

    public function update(StorePageRequest $request, Page $page): RedirectResponse
    {
        $data = $this->prepared($request);

        if ($data['status'] === 'published' && ! $page->published_at) {
            $data['published_at'] = now();
        } elseif ($data['status'] === 'draft') {
            $data['published_at'] = null;
        }

        $page->update($data);

        return redirect()->route('admin.pages.index')->with('status', 'صفحه به‌روزرسانی شد.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'صفحه حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function prepared(StorePageRequest $request): array
    {
        $data = $request->validated();
        $data['slug'] = ($data['slug'] ?? null) ?: PersianSlug::make($data['title']);

        return $data;
    }
}
