<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Cart\CartResolver;
use App\Services\Cart\Reorder;
use Illuminate\Http\RedirectResponse;
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

    public function reorder(Request $request, Order $order, Reorder $reorder, CartResolver $resolver): RedirectResponse
    {
        abort_unless($order->customer_id === $request->user('customer')->id, 404);

        $result = $reorder->handle($order, $resolver->resolveForWrite($request));

        return redirect()->route('cart.show')
            ->with('status', $result['added'] > 0
                ? $result['added'].' قلم از سفارش '.$order->order_number.' با قیمت‌های امروز به سبد خرید اضافه شد.'
                : 'هیچ قلمی از این سفارش به سبد اضافه نشد.')
            ->with('reorder_skipped', $result['skipped']);
    }
}
