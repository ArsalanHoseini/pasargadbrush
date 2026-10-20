<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Media\StoreUploadedMedia;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(): View
    {
        $media = Media::query()->latest()->paginate(24);

        return view('admin.media.index', ['media' => $media]);
    }

    public function store(StoreMediaRequest $request, StoreUploadedMedia $storeUploadedMedia): RedirectResponse
    {
        $storeUploadedMedia->handle(
            $request->file('file'),
            $request->user()->id,
            $request->string('alt_text')->value() ?: null,
        );

        return redirect()->route('admin.media.index')->with('status', 'فایل با موفقیت آپلود شد.');
    }

    public function destroy(Media $media, StoreUploadedMedia $storeUploadedMedia): RedirectResponse
    {
        $storeUploadedMedia->delete($media);

        return redirect()->route('admin.media.index')->with('status', 'فایل حذف شد.');
    }
}
