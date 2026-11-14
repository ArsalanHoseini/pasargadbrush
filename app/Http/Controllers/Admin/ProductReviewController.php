<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Moderation of product reviews — same flow as blog comments.
 */
class ProductReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        return view('admin.product-reviews.index', [
            'status' => $status,
            'reviews' => ProductReview::query()
                ->with('product')
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function approve(ProductReview $productReview): RedirectResponse
    {
        $productReview->update(['status' => 'approved']);

        return back()->with('status', 'نظر تأیید شد و در صفحه‌ی محصول نمایش داده می‌شود.');
    }

    public function spam(ProductReview $productReview): RedirectResponse
    {
        $productReview->update(['status' => 'spam']);

        return back()->with('status', 'نظر به عنوان اسپم علامت‌گذاری شد.');
    }

    public function destroy(ProductReview $productReview): RedirectResponse
    {
        $productReview->delete();

        return back()->with('status', 'نظر حذف شد.');
    }

    /**
     * Approve, mark as spam or delete several reviews at once.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['approve', 'spam', 'delete'])],
        ], ['ids.required' => 'هیچ موردی انتخاب نشده است.']);

        $selected = ProductReview::query()->whereKey($data['ids']);

        $count = match ($data['action']) {
            'approve' => $selected->update(['status' => 'approved']),
            'spam' => $selected->update(['status' => 'spam']),
            'delete' => $selected->delete(),
        };

        return back()->with('status', $count.' نظر '.match ($data['action']) {
            'approve' => 'تأیید شد.',
            'spam' => 'به عنوان اسپم علامت‌گذاری شد.',
            'delete' => 'حذف شد.',
        });
    }
}
