<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QuoteRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'productCount' => Product::count(),
            'activeProductCount' => Product::where('is_active', true)->count(),
            'categoryCount' => ProductCategory::count(),
            'newQuoteRequestCount' => QuoteRequest::where('status', 'new')->count(),
            'recentQuoteRequests' => QuoteRequest::with('product')->latest()->limit(5)->get(),
            'pendingOrderCount' => Order::where('status', 'pending')->count(),
            'recentOrders' => Order::with('customer')->latest('placed_at')->limit(5)->get(),
        ]);
    }
}
