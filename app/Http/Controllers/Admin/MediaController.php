<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\StoreUploadedMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MediaController extends Controller
{
    /**
     * The library page, or — for the media picker modal in the product/post
     * forms and the editor — the same paginated list as JSON.
     */
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $media = Media::query()
            ->select('media.*')
            ->addSelect(['usage_count' => DB::table('mediables')->selectRaw('count(*)')->whereColumn('mediables.media_id', 'media.id')])
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('alt_text', 'like', "%{$search}%")
                ->orWhere('path', 'like', "%{$search}%")))
            ->when($request->query('usage') === 'unused', fn (Builder $query) => $query->whereNotExists(
                fn ($query) => $query->select(DB::raw(1))->from('mediables')->whereColumn('mediables.media_id', 'media.id')
            ))
            ->latest()
            ->latest('id')
            ->paginate($request->wantsJson() ? 30 : 36)
            ->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $media->getCollection()->map(fn (Media $item) => $item->toPickerArray())->values(),
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'total' => $media->total(),
            ]);
        }

        return view('admin.media.index', ['media' => $media]);
    }

    public function store(StoreMediaRequest $request, StoreUploadedMedia $storeUploadedMedia): RedirectResponse|JsonResponse
    {
        $media = $storeUploadedMedia->handle(
            $request->file('file'),
            $request->user()->id,
            $request->string('alt_text')->value() ?: null,
        );

        if ($request->wantsJson()) {
            return response()->json(['data' => $media->toPickerArray()], 201);
        }

        return redirect()->route('admin.media.index')->with('status', 'فایل با موفقیت آپلود شد.');
    }

    /**
     * Edit an image's alt text (shown to search engines and screen readers).
     */
    public function update(Request $request, Media $media): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['alt_text' => ['nullable', 'string', 'max:255']]);
        $media->update(['alt_text' => $data['alt_text'] ?? null]);

        if ($request->wantsJson()) {
            return response()->json(['data' => $media->toPickerArray()]);
        }

        return back(fallback: route('admin.media.index'))->with('status', 'متن جایگزین ذخیره شد.');
    }

    public function destroy(Media $media, StoreUploadedMedia $storeUploadedMedia): RedirectResponse
    {
        $storeUploadedMedia->delete($media);

        return back(fallback: route('admin.media.index'))->with('status', 'فایل حذف شد.');
    }
}
