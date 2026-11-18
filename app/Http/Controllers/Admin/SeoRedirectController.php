<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SeoRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Staff view/edit of SEO redirects: those generated from the old WordPress
 * site, those created automatically on slug changes, and manual ones.
 * Anything staff create or edit here becomes "manual" and is never
 * overwritten by wp:import-redirects.
 */
class SeoRedirectController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $source = $request->query('source');

        return view('admin.redirects.index', [
            'search' => $search,
            'source' => $source,
            'redirects' => SeoRedirect::query()
                ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                    ->where('from_path', 'like', '%'.SeoRedirect::normalizePath($search).'%')
                    ->orWhere('to_path', 'like', '%'.$search.'%')))
                ->when($source, fn ($query) => $query->where('source', $source))
                ->orderByDesc('hits_count')
                ->orderBy('from_path')
                ->orderBy('id')
                ->paginate(50)
                ->withQueryString(),
            'totals' => SeoRedirect::query()->selectRaw('source, count(*) as total, sum(hits_count) as hits')->groupBy('source')->get()->keyBy('source'),
        ]);
    }

    public function create(): View
    {
        return view('admin.redirects.form', ['redirect' => new SeoRedirect(['status_code' => 301, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        SeoRedirect::create([...$this->validated($request), 'source' => SeoRedirect::SOURCE_MANUAL]);

        return redirect()->route('admin.redirects.index')->with('status', 'ریدایرکت اضافه شد.');
    }

    public function edit(SeoRedirect $redirect): View
    {
        return view('admin.redirects.form', ['redirect' => $redirect]);
    }

    public function update(Request $request, SeoRedirect $redirect): RedirectResponse
    {
        $redirect->update([...$this->validated($request, $redirect), 'source' => SeoRedirect::SOURCE_MANUAL]);

        return redirect()->route('admin.redirects.index')->with('status', 'ریدایرکت به‌روزرسانی شد.');
    }

    public function destroy(SeoRedirect $redirect): RedirectResponse
    {
        $redirect->delete();

        return redirect()->route('admin.redirects.index')->with('status', 'ریدایرکت حذف شد.');
    }

    /**
     * @return array{from_path: string, to_path: ?string, status_code: int, is_active: bool, notes: ?string}
     */
    private function validated(Request $request, ?SeoRedirect $redirect = null): array
    {
        if (is_string($request->input('from_path'))) {
            $request->merge(['from_path' => SeoRedirect::normalizePath($request->input('from_path'))]);
        }

        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:255', 'not_in:/', Rule::unique('seo_redirects', 'from_path')->ignore($redirect)],
            'status_code' => ['required', Rule::in([301, 302, 410])],
            'to_path' => ['nullable', 'required_unless:status_code,410', 'string', 'max:255', 'regex:#^(/|https?://)#'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'to_path.regex' => 'آدرس مقصد باید با / شروع شود (مثلاً /products/برس-اسب) یا یک آدرس کامل https:// باشد.',
            'from_path.unique' => 'برای این آدرس قبلاً ریدایرکت تعریف شده است.',
        ], [
            'from_path' => 'آدرس قدیمی',
            'to_path' => 'آدرس جدید',
            'status_code' => 'نوع',
        ]);

        return [
            'from_path' => $data['from_path'],
            'to_path' => (int) $data['status_code'] === 410 ? null : $data['to_path'],
            'status_code' => (int) $data['status_code'],
            'is_active' => $request->boolean('is_active'),
            'notes' => $data['notes'] ?? null,
        ];
    }
}
