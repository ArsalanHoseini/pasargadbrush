<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartItemRequest;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Cart\CartManager;
use App\Services\Cart\CartResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private CartResolver $resolver,
        private CartManager $manager,
    ) {}

    public function show(Request $request): View
    {
        $cart = $this->resolver->resolveForDisplay($request);

        return view('cart.show', [
            'cart' => $cart,
            'total' => $cart ? $this->manager->totalRial($cart) : 0,
        ]);
    }

    public function store(AddCartItemRequest $request)
    {
        $variant = ProductVariant::with('product')->findOrFail($request->integer('product_variant_id'));
        abort_unless($variant->product->is_active && $variant->product->type === 'priced', 404);

        $cart = $this->resolver->resolveForWrite($request);
        $this->manager->addItem($cart, $variant, $request->integer('quantity'));

        return back()->with('status', 'به سبد خرید اضافه شد.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($request, $cartItem);

        $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:1000']]);
        $this->manager->updateQuantity($cartItem, $request->integer('quantity'));

        return back()->with('status', 'سبد خرید به‌روزرسانی شد.');
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($request, $cartItem);

        $cartItem->delete();

        return back()->with('status', 'از سبد خرید حذف شد.');
    }

    private function authorizeCartItem(Request $request, CartItem $cartItem): void
    {
        $cart = $this->resolver->resolveForDisplay($request);

        abort_unless($cart && $cartItem->cart_id === $cart->id, 404);
    }
}
