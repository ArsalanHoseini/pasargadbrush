<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Support\PersianDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuoteRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = trim(PersianDigits::toLatin((string) $request->query('q', '')));

        $quoteRequests = QuoteRequest::query()
            ->with('product')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.quote-requests.index', [
            'quoteRequests' => $quoteRequests,
            'status' => $status,
            'statusCounts' => QuoteRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function edit(QuoteRequest $quoteRequest): View
    {
        return view('admin.quote-requests.edit', ['quoteRequest' => $quoteRequest->load('product', 'order', 'conversation')]);
    }

    public function update(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'contacted', 'quoted', 'closed'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $quoteRequest->update($data);

        return redirect()->route('admin.quote-requests.index')->with('status', 'درخواست به‌روزرسانی شد.');
    }

    public function destroy(QuoteRequest $quoteRequest): RedirectResponse
    {
        $quoteRequest->delete();

        return redirect()->route('admin.quote-requests.index')->with('status', 'درخواست حذف شد.');
    }
}
