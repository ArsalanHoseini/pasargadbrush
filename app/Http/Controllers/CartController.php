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
            'problems' => $cart ? $this->manager->problems($cart) : [],
        ]);
    }

    public function store(AddCartItemRequest $request)
    {
        $variant = ProductVariant::with('product')->findOrFail($request->integer('product_variant_id'));
        abort_unless($variant->product->is_active && $variant->product->type === 'priced', 404);

        $existingCart = $this->resolver->resolveForDisplay($request);
        $quantityAfterAdding = $existingCart
            ? $this->manager->quantityAfterAdding($existingCart, $variant, $request->integer('quantity'))
            : min($request->integer('quantity'), CartManager::MAX_QUANTITY);

        $reason = $variant->unavailableReason($quantityAfterAdding);

        if ($reason !== null) {
            return back()->withErrors(['quantity' => $reason])->withInput();
        }

        $cart = $this->resolver->resolveForWrite($request);
        $this->manager->addItem($cart, $variant, $request->integer('quantity'));

        return back()->with('status', 'به سبد خرید اضافه شد.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($request, $cartItem);

        $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.CartManager::MAX_QUANTITY]]);
        $quantity = $request->integer('quantity');

        // Lowering a quantity or removing a line is always allowed, even for
        // an item that has since become unavailable — only increases are checked.
        if ($quantity > $cartItem->quantity) {
            $reason = $cartItem->variant->unavailableReason($quantity);

            if ($reason !== null) {
                return back()->withErrors(['quantity' => $reason]);
            }
        }

        $this->manager->updateQuantity($cartItem, $quantity);

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
