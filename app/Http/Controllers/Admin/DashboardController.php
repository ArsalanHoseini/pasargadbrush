<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QuoteRequest;
use App\Services\Admin\DashboardMetrics;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardMetrics $metrics): View
    {
        return view('admin.dashboard', [
            'salesPeriods' => $metrics->salesPeriods(),
            'dailySales' => $metrics->dailySales(),
            'actionItems' => $metrics->actionItems(),
            'topProducts' => $metrics->topProducts(),
            'customerCounts' => $metrics->customerCounts(),
            'productCount' => Product::count(),
            'activeProductCount' => Product::where('is_active', true)->count(),
            'categoryCount' => ProductCategory::count(),
            'newQuoteRequestCount' => QuoteRequest::where('status', 'new')->count(),
            'recentQuoteRequests' => QuoteRequest::with('product')->latest()->limit(6)->get(),
            'recentOrders' => Order::with('customer')->latest('placed_at')->limit(6)->get(),
        ]);
    }
}
