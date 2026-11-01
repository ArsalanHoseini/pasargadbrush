<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuoteRequestController extends Controller
{
    /**
     * Customer-facing wording for the staff-side statuses.
     */
    public const STATUS_LABELS = [
        'new' => 'در انتظار بررسی',
        'contacted' => 'کارشناس با شما تماس گرفته است',
        'quoted' => 'قیمت اعلام شد',
        'closed' => 'بسته شده',
    ];

    /**
     * Requests sent while logged in, plus earlier guest requests sent with
     * the account's (verified) mobile number.
     *
     * @return Builder<QuoteRequest>
     */
    public static function visibleTo(Customer $customer): Builder
    {
        return QuoteRequest::query()->where(fn (Builder $query) => $query
            ->where('customer_id', $customer->id)
            ->orWhere(fn (Builder $guest) => $guest->whereNull('customer_id')->where('phone', $customer->phone)));
    }

    public function index(Request $request): View
    {
        return view('account.quote-requests.index', [
            'quoteRequests' => self::visibleTo($request->user('customer'))
                ->with('product', 'order', 'conversation')
                ->latest()
                ->paginate(15),
        ]);
    }
}
