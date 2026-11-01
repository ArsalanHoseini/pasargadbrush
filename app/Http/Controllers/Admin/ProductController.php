<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\StoreUploadedMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDimensionField;
use App\Models\ProductVariant;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private StoreUploadedMedia $mediaUploader) {}

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
            $this->syncDimensionFields($product, $request->input('dimension_fields', []));
            $this->syncSchematics($product, $request);

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
            $this->syncDimensionFields($product, $request->input('dimension_fields', []));
            $this->syncSchematics($product, $request);
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
            'dimensionFields' => $product->exists ? $product->dimensionFields : collect(),
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
        $data = $request->safe()->except(['category_ids', 'image_ids', 'variants', 'specifications', 'schematics', 'remove_schematic_ids', 'dimension_fields']);
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

    /**
     * Same keep/update/delete pattern as variants. Past cart/order lines are
     * unaffected by edits here — they carry their own snapshot.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function syncDimensionFields(Product $product, array $fields): void
    {
        $keptIds = [];

        foreach (array_values($fields) as $position => $field) {
            $isChoice = ($field['type'] ?? null) === ProductDimensionField::TYPE_CHOICE;

            $attributes = [
                'type' => $isChoice ? ProductDimensionField::TYPE_CHOICE : ProductDimensionField::TYPE_NUMBER,
                'code' => trim((string) ($field['code'] ?? '')),
                'label' => trim($field['label']),
                'unit' => $field['unit'] ?? 'mm',
                // Entered one per line or separated by commas («،» too).
                'options' => $isChoice
                    ? array_values(array_unique(array_filter(array_map('trim', preg_split('/[
,،]+/u', (string) ($field['options'] ?? '')) ?: []))))
                    : null,
                'is_required' => ! empty($field['is_required']),
                'min_value' => filled($field['min_value'] ?? null) ? $field['min_value'] : null,
                'max_value' => filled($field['max_value'] ?? null) ? $field['max_value'] : null,
                'sort_order' => $position,
            ];

            $existing = ! empty($field['id'])
                ? $product->dimensionFields()->whereKey($field['id'])->first()
                : null;

            if ($existing) {
                $existing->update($attributes);
                $keptIds[] = $existing->id;

                continue;
            }

            $keptIds[] = $product->dimensionFields()->create($attributes)->id;
        }

        $product->dimensionFields()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Schematics are uploaded straight from the product form into the media
     * library and appended after the existing ones. Removing one only
     * unlinks it; the file stays in the library.
     */
    private function syncSchematics(Product $product, StoreProductRequest $request): void
    {
        $removeIds = array_map('intval', $request->input('remove_schematic_ids', []));

        if ($removeIds !== []) {
            $product->schematics()->detach($removeIds);
        }

        $position = (int) $product->schematics()->max('mediables.sort_order') + 1;

        foreach ($request->file('schematics', []) as $file) {
            $media = $this->mediaUploader->handle($file, $request->user()?->id, 'شماتیک '.$product->name);
            $product->schematics()->attach($media->id, ['role' => 'schematic', 'sort_order' => $position++]);
        }
    }
}
