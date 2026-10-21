<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Models\Order;
use App\Services\Cart\CartManager;
use App\Services\Cart\CartResolver;
use App\Services\Checkout\PlaceOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartResolver $resolver,
        private CartManager $manager,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $cart = $this->resolver->resolveForDisplay($request);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.show')->with('status', 'سبد خرید شما خالی است.');
        }

        return view('checkout.create', [
            'cart' => $cart,
            'total' => $this->manager->totalRial($cart),
        ]);
    }

    public function store(StoreCheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $cart = $this->resolver->resolveForDisplay($request);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.show')->with('status', 'سبد خرید شما خالی است.');
        }

        $order = $placeOrder->handle($cart, $request->validated());

        $request->session()->put('last_order_id', $order->id);

        return redirect()->route('checkout.confirmation');
    }

    public function confirmation(Request $request): View|RedirectResponse
    {
        $orderId = $request->session()->get('last_order_id');

        if (! $orderId) {
            return redirect()->route('home');
        }

        $order = Order::with('items', 'shippingAddress')->find($orderId);

        if (! $order) {
            return redirect()->route('home');
        }

        return view('checkout.confirmation', ['order' => $order]);
    }
}
