<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        return view('account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $customer->orders()->latest('placed_at')->limit(5)->get(),
            'openOrderCount' => $customer->orders()->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'recentQuoteRequests' => QuoteRequestController::visibleTo($customer)->with('product')->latest()->limit(3)->get(),
        ]);
    }
}
