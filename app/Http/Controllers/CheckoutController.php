<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Models\Order;
use App\Services\Cart\CartManager;
use App\Services\Cart\CartResolver;
use App\Services\Checkout\CartNotOrderable;
use App\Services\Checkout\NotifyOrderPlaced;
use App\Services\Checkout\PlaceOrder;
use App\Services\Checkout\ShippingCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private CartResolver $resolver,
        private CartManager $manager,
    ) {}

    public function create(Request $request, ShippingCalculator $shipping): View|RedirectResponse
    {
        $cart = $this->resolver->resolveForDisplay($request);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.show')->with('status', 'سبد خرید شما خالی است.');
        }

        if ($this->manager->problems($cart) !== []) {
            return $this->backToCartWithProblems();
        }

        $total = $this->manager->totalRial($cart);

        return view('checkout.create', [
            'cart' => $cart,
            'total' => $total,
            'shipping' => $shipping->quote($total),
            'priceNeedsConfirmation' => $this->manager->hasCustomizedItems($cart),
            'customer' => $request->user('customer'),
            'savedAddresses' => $request->user('customer')?->savedAddresses ?? collect(),
        ]);
    }

    public function store(StoreCheckoutRequest $request, PlaceOrder $placeOrder, NotifyOrderPlaced $notifyOrderPlaced): RedirectResponse
    {
        $cart = $this->resolver->resolveForDisplay($request);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.show')->with('status', 'سبد خرید شما خالی است.');
        }

        try {
            $order = $placeOrder->handle($cart, $request->validated());
        } catch (CartNotOrderable) {
            return $this->backToCartWithProblems();
        }

        $notifyOrderPlaced->handle($order);

        $request->session()->put('last_order_id', $order->id);

        return redirect()->route('checkout.confirmation');
    }

    /**
     * The cart page itself lists the reason next to each affected line.
     */
    private function backToCartWithProblems(): RedirectResponse
    {
        return redirect()->route('cart.show')->withErrors([
            'cart' => 'موجودی یا وضعیت برخی از محصولات سبد خرید شما تغییر کرده است. لطفاً موارد مشخص‌شده را اصلاح کنید و دوباره ادامه دهید.',
        ]);
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
