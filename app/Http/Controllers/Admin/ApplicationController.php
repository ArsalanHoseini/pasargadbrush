<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\StoreUploadedMedia;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The shared list of applications (uses/industries) with their icons,
 * picked per product in the product form.
 */
class ApplicationController extends Controller
{
    public function __construct(private StoreUploadedMedia $mediaUploader) {}

    public function index(): View
    {
        return view('admin.applications.index', [
            'applications' => Application::query()->with('icon')->withCount('products')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.applications.form', ['application' => new Application]);
    }

    public function store(Request $request): RedirectResponse
    {
        $application = Application::create($this->validated($request));
        $this->storeIcon($request, $application);

        return redirect()->route('admin.applications.index')->with('status', 'کاربرد اضافه شد.');
    }

    public function edit(Application $application): View
    {
        return view('admin.applications.form', ['application' => $application->load('icon')]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $application->update($this->validated($request));
        $this->storeIcon($request, $application);

        return redirect()->route('admin.applications.index')->with('status', 'کاربرد به‌روزرسانی شد.');
    }

    public function destroy(Application $application): RedirectResponse
    {
        $application->delete();

        return redirect()->route('admin.applications.index')->with('status', 'کاربرد حذف شد و از صفحه‌ی محصولات برداشته شد.');
    }

    /**
     * @return array{name: string, sort_order: int}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            // SVG excluded for the same XSS reason as the media library upload.
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ], attributes: ['name' => 'عنوان کاربرد', 'icon' => 'آیکون']);

        return ['name' => $data['name'], 'sort_order' => (int) ($data['sort_order'] ?? 0)];
    }

    private function storeIcon(Request $request, Application $application): void
    {
        if ($request->hasFile('icon')) {
            $media = $this->mediaUploader->handle($request->file('icon'), $request->user()?->id, 'آیکون '.$application->name);
            $application->update(['icon_media_id' => $media->id]);
        }
    }
}
