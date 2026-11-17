<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\QuoteRequest;
use App\Support\CsvExport;
use App\Support\PersianDate;
use App\Support\PersianDigits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteRequestController extends Controller
{
    public const STATUS_LABELS = ['new' => 'جدید', 'contacted' => 'تماس گرفته شد', 'quoted' => 'قیمت ارسال شد', 'closed' => 'بسته شده'];

    public function index(Request $request): View
    {
        return view('admin.quote-requests.index', [
            'quoteRequests' => $this->filteredQuoteRequests($request)->with('product')->paginate(20)->withQueryString(),
            'status' => $request->query('status'),
            'statusCounts' => QuoteRequest::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return CsvExport::download('quote-requests', [
            'شماره', 'تاریخ', 'نام', 'شرکت', 'موبایل', 'ایمیل', 'محصول', 'تعداد درخواستی', 'وضعیت', 'پیام',
        ], $this->filteredQuoteRequests($request)->with('product')->lazy(500)->map(fn (QuoteRequest $quoteRequest) => [
            $quoteRequest->id,
            PersianDate::format($quoteRequest->created_at, PersianDate::COMPACT),
            $quoteRequest->name,
            $quoteRequest->company,
            $quoteRequest->phone,
            $quoteRequest->email,
            $quoteRequest->product?->name ?? 'عمومی',
            $quoteRequest->requested_quantity,
            self::STATUS_LABELS[$quoteRequest->status] ?? $quoteRequest->status,
            $quoteRequest->message,
        ]));
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in([...array_map(fn (string $status) => 'status:'.$status, array_keys(self::STATUS_LABELS)), 'delete'])],
        ], ['ids.required' => 'هیچ درخواستی انتخاب نشده است.']);

        $selected = QuoteRequest::query()->whereKey($data['ids']);
        AuditLog::record('quote_request.bulk', null, ['action' => $data['action'], 'ids' => array_map('intval', $data['ids'])]);

        if ($data['action'] === 'delete') {
            $count = $selected->delete();

            return back()->with('status', $count.' درخواست حذف شد.');
        }

        $count = $selected->update(['status' => substr($data['action'], strlen('status:'))]);

        return back()->with('status', 'وضعیت '.$count.' درخواست به‌روزرسانی شد.');
    }

    public function edit(QuoteRequest $quoteRequest): View
    {
        return view('admin.quote-requests.edit', ['quoteRequest' => $quoteRequest->load('product', 'order', 'conversation')]);
    }

    public function update(Request $request, QuoteRequest $quoteRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::STATUS_LABELS))],
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

    /**
     * @return Builder<QuoteRequest>
     */
    private function filteredQuoteRequests(Request $request): Builder
    {
        $search = trim(PersianDigits::toLatin((string) $request->query('q', '')));
        $from = PersianDate::parse($request->query('from'));
        $to = PersianDate::parse($request->query('to'));

        return QuoteRequest::query()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($query) => $query->where('name', 'like', "%{$search}%"))))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<', $to->modify('+1 day')))
            ->latest()
            ->latest('id');
    }
}
