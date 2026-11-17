<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductCategoryRequest;
use App\Models\ProductCategory;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $categories = ProductCategory::query()
            ->withCount('primaryProducts')
            ->with('parent')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('admin.categories.form', [
            'category' => new ProductCategory,
            'parents' => ProductCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        ProductCategory::create($this->prepared($request));

        return redirect()->route('admin.categories.index')->with('status', 'دسته‌بندی ایجاد شد.');
    }

    public function edit(ProductCategory $productCategory): View
    {
        return view('admin.categories.form', [
            'category' => $productCategory,
            'parents' => ProductCategory::query()->where('id', '!=', $productCategory->id)->orderBy('name')->get(),
        ]);
    }

    public function update(StoreProductCategoryRequest $request, ProductCategory $productCategory): RedirectResponse
    {
        $productCategory->update($this->prepared($request));

        return redirect()->route('admin.categories.index')->with('status', 'دسته‌بندی به‌روزرسانی شد.');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        if ($productCategory->primaryProducts()->exists() || $productCategory->children()->exists()) {
            return redirect()->route('admin.categories.index')
                ->with('status', 'این دسته دارای محصول یا زیردسته است و قابل حذف نیست.');
        }

        $productCategory->delete();

        return redirect()->route('admin.categories.index')->with('status', 'دسته‌بندی حذف شد.');
    }

    /**
     * @return array<string, mixed>
     */
    private function prepared(StoreProductCategoryRequest $request): array
    {
        $data = $request->validated();
        $data['slug'] = ($data['slug'] ?? null) ?: PersianSlug::make($data['name']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
