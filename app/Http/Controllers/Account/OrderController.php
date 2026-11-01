<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.orders.index', [
            'orders' => $request->user('customer')->orders()->latest('placed_at')->paginate(15),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        // 404 rather than 403, so order ids can't be probed.
        abort_unless($order->customer_id === $request->user('customer')->id, 404);

        return view('account.orders.show', ['order' => $order->load('items', 'shippingAddress', 'conversation')]);
    }
}
