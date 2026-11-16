<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\StoreUploadedMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDimensionField;
use App\Models\ProductVariant;
use App\Services\Admin\DashboardMetrics;
use App\Support\PersianDigits;
use App\Support\PersianSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private StoreUploadedMedia $mediaUploader) {}

    public function index(Request $request): View
    {
        $search = trim(PersianDigits::toLatin((string) $request->query('q', '')));
        $categoryId = $request->integer('category');

        $products = Product::query()
            ->with(['primaryCategory', 'images', 'variants'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhereHas('variants', fn ($query) => $query->where('sku', 'like', "%{$search}%"))))
            ->when($categoryId, fn ($query) => $query->where(fn ($query) => $query
                ->where('primary_category_id', $categoryId)
                ->orWhereHas('categories', fn ($query) => $query->whereKey($categoryId))))
            ->when(in_array($request->query('type'), ['priced', 'quote'], true), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($request->query('stock') === 'low', fn ($query) => $query->whereHas('variants', fn ($query) => $query
                ->whereNotNull('stock_quantity')
                ->where('stock_quantity', '<=', DashboardMetrics::LOW_STOCK_THRESHOLD)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categoryOptions' => ProductCategory::orderBy('name')->pluck('name', 'id'),
        ]);
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
            $this->syncHighlights($product, $request);
            $product->applications()->sync($request->input('application_ids', []));

            return $product;
        });

        AuditLog::record('product.created', $product, ['name' => $product->name]);

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
            $this->syncHighlights($product, $request);
            $product->applications()->sync($request->input('application_ids', []));
        });

        AuditLog::record('product.updated', $product, ['name' => $product->name]);

        return redirect()->route('admin.products.edit', $product)->with('status', 'محصول به‌روزرسانی شد.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return redirect()->route('admin.products.index')->with('status', 'این محصول در سفارش‌ها استفاده شده و قابل حذف نیست.');
        }

        AuditLog::record('product.deleted', $product, ['name' => $product->name]);
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'محصول حذف شد.');
    }

    /**
     * Activate, deactivate or delete several products. Products already
     * used in orders are never deleted (same rule as the single delete).
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
        ], ['ids.required' => 'هیچ محصولی انتخاب نشده است.']);

        $selected = Product::query()->whereKey($data['ids']);
        AuditLog::record('product.bulk', null, ['action' => $data['action'], 'ids' => array_map('intval', $data['ids'])]);

        if ($data['action'] !== 'delete') {
            $count = $selected->update(['is_active' => $data['action'] === 'activate']);

            return back()->with('status', $count.' محصول '.($data['action'] === 'activate' ? 'فعال' : 'غیرفعال').' شد.');
        }

        $deletable = $selected->doesntHave('orderItems')->get();
        $deletable->each->delete();
        $kept = count($data['ids']) - $deletable->count();

        return back()->with('status', $deletable->count().' محصول حذف شد'.($kept > 0 ? ' ('.$kept.' محصول در سفارش‌ها استفاده شده و حذف نشد).' : '.'));
    }

    private function form(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => ProductCategory::query()->orderBy('name')->get(),
            'galleryItems' => $this->galleryItems($product),
            'selectedCategoryIds' => $product->exists ? $product->categories->pluck('id')->all() : [],
            'variants' => $product->exists ? $product->variants()->orderBy('sort_order')->get() : collect(),
            'dimensionFields' => $product->exists ? $product->dimensionFields : collect(),
            'highlights' => $product->exists ? $product->highlights()->with('image')->get() : collect(),
            'applications' => Application::query()->with('icon')->orderBy('sort_order')->orderBy('name')->get(),
            'selectedApplicationIds' => $product->exists ? $product->applications()->pluck('applications.id')->all() : [],
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
        $data = $request->safe()->except(['category_ids', 'image_ids', 'variants', 'specifications', 'schematics', 'remove_schematic_ids', 'dimension_fields', 'highlights', 'application_ids']);
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

    /**
     * The gallery for the media picker, in order: what was just submitted
     * (after a validation error) or the product's saved images.
     *
     * @return list<array<string, mixed>>
     */
    private function galleryItems(Product $product): array
    {
        if (session()->hasOldInput()) {
            $ids = array_map('intval', (array) old('image_ids', []));
            $media = Media::query()->whereKey($ids)->get()->keyBy('id');

            return collect($ids)->map(fn (int $id) => $media->get($id)?->toPickerArray())->filter()->values()->all();
        }

        return $product->exists ? $product->images->map->toPickerArray()->values()->all() : [];
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

    /**
     * Key features beside the product image: same keep/update/delete pattern
     * as variants. Each row may upload a new image (replacing the old one)
     * or drop its current image.
     */
    private function syncHighlights(Product $product, StoreProductRequest $request): void
    {
        $keptIds = [];
        $position = 0;

        foreach ($request->input('highlights', []) as $index => $row) {
            $highlight = ! empty($row['id']) ? $product->highlights()->whereKey($row['id'])->first() : null;
            $attributes = ['title' => trim($row['title']), 'sort_order' => $position++];

            if ($request->hasFile("highlights.{$index}.image")) {
                $attributes['media_id'] = $this->mediaUploader
                    ->handle($request->file("highlights.{$index}.image"), $request->user()?->id, $attributes['title'])
                    ->id;
            } elseif (! empty($row['remove_image'])) {
                $attributes['media_id'] = null;
            }

            if ($highlight) {
                $highlight->update($attributes);
            } else {
                $highlight = $product->highlights()->create($attributes);
            }

            $keptIds[] = $highlight->id;
        }

        $product->highlights()->whereNotIn('id', $keptIds)->delete();
    }
}
