<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with('primaryCategory')
            ->latest()
            ->paginate(20);

        return view('admin.products.index', ['products' => $products]);
    }

    public function create(): View
    {
        return $this->form(new Product);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request) {
            $data = $this->prepared($request);
            $data['published_at'] = $data['is_active'] ? now() : null;

            $product = Product::create($data);
            $this->syncCategories($product, $request->input('category_ids', []));
            $this->syncImages($product, $request->input('image_ids', []));
            $this->syncVariants($product, $request->input('variants', []));

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)->with('status', 'محصول ایجاد شد.');
    }

    public function edit(Product $product): View
    {
        return $this->form($product);
    }

    public function update(StoreProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $data = $this->prepared($request);

            if ($data['is_active'] && ! $product->published_at) {
                $data['published_at'] = now();
            } elseif (! $data['is_active']) {
                $data['published_at'] = null;
            }

            $product->update($data);
            $this->syncCategories($product, $request->input('category_ids', []));
            $this->syncImages($product, $request->input('image_ids', []));
            $this->syncVariants($product, $request->input('variants', []));
        });

        return redirect()->route('admin.products.edit', $product)->with('status', 'محصول به‌روزرسانی شد.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return redirect()->route('admin.products.index')->with('status', 'این محصول در سفارش‌ها استفاده شده و قابل حذف نیست.');
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'محصول حذف شد.');
    }

    private function form(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => ProductCategory::query()->orderBy('name')->get(),
            'media' => Media::query()->latest()->limit(90)->get(),
            'selectedCategoryIds' => $product->exists ? $product->categories->pluck('id')->all() : [],
            'selectedImageIds' => $product->exists ? $product->images->pluck('id')->all() : [],
            'variants' => $product->exists ? $product->variants()->orderBy('sort_order')->get() : collect(),
            'specificationRows' => collect($product->specifications ?? [])
                ->map(fn ($value, $key) => ['key' => $key, 'value' => $value])
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepared(StoreProductRequest $request): array
    {
        $data = $request->safe()->except(['category_ids', 'image_ids', 'variants', 'specifications']);
        $data['slug'] = ($data['slug'] ?? null) ?: PersianSlug::make($data['name']);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');
        $data['specifications'] = $this->specificationsFromRequest($request);

        return $data;
    }

    /** @return array<string, string>|null */
    private function specificationsFromRequest(StoreProductRequest $request): ?array
    {
        $rows = collect($request->input('specifications', []))
            ->filter(fn ($row) => filled($row['key'] ?? null) && filled($row['value'] ?? null))
            ->mapWithKeys(fn ($row) => [$row['key'] => $row['value']]);

        return $rows->isEmpty() ? null : $rows->all();
    }

    /** @param array<int, int|string> $categoryIds */
    private function syncCategories(Product $product, array $categoryIds): void
    {
        $product->categories()->sync($categoryIds);
    }

    /** @param array<int, int|string> $imageIds */
    private function syncImages(Product $product, array $imageIds): void
    {
        $product->images()->detach();

        foreach (array_values($imageIds) as $position => $mediaId) {
            $product->images()->attach($mediaId, ['role' => 'gallery', 'sort_order' => $position]);
        }
    }

    /** @param array<int, array<string, mixed>> $variants */
    private function syncVariants(Product $product, array $variants): void
    {
        $keptIds = [];

        foreach ($variants as $position => $variant) {
            $attributes = [
                'product_id' => $product->id,
                'label' => $variant['label'],
                'price_rial' => ((int) $variant['price_toman']) * 10,
                'stock_quantity' => $variant['stock_quantity'] ?? null,
                'is_default' => ! empty($variant['is_default']),
                'sort_order' => $position,
            ];

            if (! empty($variant['id'])) {
                $row = ProductVariant::where('id', $variant['id'])->where('product_id', $product->id)->first();
                if ($row) {
                    $row->update($attributes);
                    $keptIds[] = $row->id;

                    continue;
                }
            }

            $attributes['sku'] = $product->sku.'-V'.($position + 1).'-'.strtoupper(substr(md5((string) microtime(true)), 0, 4));
            $keptIds[] = ProductVariant::create($attributes)->id;
        }

        $product->variants()->whereNotIn('id', $keptIds)->delete();
    }
}
