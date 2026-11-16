<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMenuRequest;
use App\Models\AuditLog;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The site's editable menus: header navigation and footer quick links.
 */
class MenuController extends Controller
{
    public function edit(string $location): View
    {
        abort_unless(array_key_exists($location, MenuItem::LOCATIONS), 404);

        $items = MenuItem::query()->topLevel($location)->with('children')->get()
            ->map(fn (MenuItem $item) => [
                ...$item->only(['label', 'type', 'url', 'open_in_new_tab']),
                'children' => $item->children->map->only(['label', 'type', 'url', 'open_in_new_tab'])->values()->all(),
            ])
            ->values()
            ->all();

        return view('admin.menus.edit', [
            'location' => $location,
            'items' => $items,
            'linkGroups' => $this->linkGroups(),
        ]);
    }

    /**
     * Replace the whole menu for one location with the submitted tree.
     */
    public function update(UpdateMenuRequest $request, string $location): RedirectResponse
    {
        abort_unless(array_key_exists($location, MenuItem::LOCATIONS), 404);

        DB::transaction(function () use ($request, $location) {
            MenuItem::query()->where('location', $location)->delete();

            foreach (array_values($request->validated('menu')) as $position => $row) {
                $parent = MenuItem::create([
                    'location' => $location,
                    'label' => trim($row['label']),
                    'type' => $row['type'],
                    'url' => $row['type'] === MenuItem::TYPE_LINK ? trim($row['url']) : null,
                    'open_in_new_tab' => (bool) ($row['open_in_new_tab'] ?? false),
                    'sort_order' => $position,
                ]);

                foreach (array_values($row['children'] ?? []) as $childPosition => $child) {
                    MenuItem::create([
                        'location' => $location,
                        'parent_id' => $parent->id,
                        'label' => trim($child['label']),
                        'type' => MenuItem::TYPE_LINK,
                        'url' => trim($child['url']),
                        'open_in_new_tab' => (bool) ($child['open_in_new_tab'] ?? false),
                        'sort_order' => $childPosition,
                    ]);
                }
            }
        });

        MenuItem::forgetCache($location);
        AuditLog::record('menu.updated', null, ['location' => $location]);

        return redirect()->route('admin.menus.edit', $location)->with('status', 'منو ذخیره شد و در سایت نمایش داده می‌شود.');
    }

    /**
     * Ready-made links the editor can add with one click.
     *
     * @return list<array{label: string, links: list<array{label: string, url: string}>}>
     */
    private function linkGroups(): array
    {
        $relative = fn (string $url): string => '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        return [
            ['label' => 'صفحات اصلی', 'links' => [
                ['label' => 'صفحه اصلی', 'url' => '/'],
                ['label' => 'مقالات', 'url' => $relative(route('blog.index'))],
                ['label' => 'پیگیری سفارش', 'url' => $relative(route('orders.track.create'))],
                ['label' => 'سبد خرید', 'url' => $relative(route('cart.show'))],
            ]],
            ['label' => 'صفحات ثابت', 'links' => Page::query()->where('status', 'published')->orderBy('title')->get(['title', 'slug'])
                ->map(fn (Page $page) => ['label' => $page->title, 'url' => $relative(route('pages.show', $page->slug))])->all()],
            ['label' => 'دسته‌های محصول', 'links' => ProductCategory::query()->orderBy('name')->get(['name', 'slug'])
                ->map(fn (ProductCategory $category) => ['label' => $category->name, 'url' => $relative(route('categories.show', $category->slug))])->all()],
        ];
    }
}
