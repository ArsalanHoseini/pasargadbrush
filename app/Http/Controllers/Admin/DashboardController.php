<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QuoteRequest;
use App\Services\Admin\DashboardMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Editors get the catalog/content parts only; sales figures are admin-only.
     */
    public function index(Request $request, DashboardMetrics $metrics): View
    {
        $catalog = [
            'productCount' => Product::count(),
            'activeProductCount' => Product::where('is_active', true)->count(),
            'categoryCount' => ProductCategory::count(),
        ];

        if (! $request->user()->isAdmin()) {
            return view('admin.dashboard', [...$catalog, 'actionItems' => $metrics->actionItems(includeSales: false)]);
        }

        return view('admin.dashboard', [...$catalog,
            'salesPeriods' => $metrics->salesPeriods(),
            'dailySales' => $metrics->dailySales(),
            'actionItems' => $metrics->actionItems(),
            'topProducts' => $metrics->topProducts(),
            'customerCounts' => $metrics->customerCounts(),
            'newQuoteRequestCount' => QuoteRequest::where('status', 'new')->count(),
            'recentQuoteRequests' => QuoteRequest::with('product')->latest()->limit(6)->get(),
            'recentOrders' => Order::with('customer')->latest('placed_at')->limit(6)->get(),
        ]);
    }
}
