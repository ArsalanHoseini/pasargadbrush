<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Support\PersianDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The admin top-bar search: orders, products, customers and quote requests
 * at once. An exact order number jumps straight to that order.
 */
class SearchController extends Controller
{
    private const RESULTS_PER_TYPE = 8;

    public function __invoke(Request $request): View|RedirectResponse
    {
        $term = trim(PersianDigits::toLatin((string) $request->query('q', '')));

        if ($term === '') {
            return view('admin.search', ['term' => $term, 'results' => null]);
        }

        $exactOrder = Order::where('order_number', $term)->first();

        if ($exactOrder) {
            return redirect()->route('admin.orders.edit', $exactOrder);
        }

        $like = '%'.$term.'%';

        $results = [
            'orders' => Order::query()
                ->where(fn ($query) => $query
                    ->where('order_number', 'like', $like)
                    ->orWhere('customer_name', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like)
                    ->orWhere('shipment_tracking_code', 'like', $like))
                ->latest('placed_at')
                ->limit(self::RESULTS_PER_TYPE)
                ->get(),
            'products' => Product::query()
                ->with('primaryCategory')
                ->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('sku', 'like', $like))
                ->orderBy('name')
                ->limit(self::RESULTS_PER_TYPE)
                ->get(),
            'customers' => Customer::query()
                ->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('company_name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like))
                ->latest()
                ->limit(self::RESULTS_PER_TYPE)
                ->get(),
            'quoteRequests' => QuoteRequest::query()
                ->with('product')
                ->where(fn ($query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('company', 'like', $like))
                ->latest()
                ->limit(self::RESULTS_PER_TYPE)
                ->get(),
        ];

        return view('admin.search', ['term' => $term, 'results' => $results]);
    }
}
