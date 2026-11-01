<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartItemRequest;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Cart\CartManager;
use App\Services\Cart\CartResolver;
use App\Services\Checkout\ShippingCalculator;
use App\Services\Customization\DimensionForm;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(
        private CartResolver $resolver,
        private CartManager $manager,
    ) {}

    public function show(Request $request, ShippingCalculator $shipping): View
    {
        $cart = $this->resolver->resolveForDisplay($request);
        $total = $cart ? $this->manager->totalRial($cart) : 0;

        return view('cart.show', [
            'cart' => $cart,
            'total' => $total,
            'shipping' => $shipping->quote($total),
            'priceNeedsConfirmation' => $cart && $this->manager->hasCustomizedItems($cart),
            'problems' => $cart ? $this->manager->problems($cart) : [],
        ]);
    }

    public function store(AddCartItemRequest $request, DimensionForm $dimensionForm)
    {
        $variant = ProductVariant::with('product.dimensionFields')->findOrFail($request->integer('product_variant_id'));
        abort_unless($variant->product->is_active && $variant->product->type === 'priced', 404);

        $customization = null;

        if ($variant->product->requiresDimensions()) {
            $dimensionForm->normalize($request);
            $validated = $request->validate(
                $dimensionForm->rules($variant->product),
                attributes: $dimensionForm->attributes($variant->product),
            );
            $customization = $dimensionForm->snapshot($variant->product, $validated);
        }

        $existingCart = $this->resolver->resolveForDisplay($request);
        $quantityAfterAdding = $existingCart
            ? $this->manager->quantityAfterAdding($existingCart, $variant, $request->integer('quantity'))
            : min($request->integer('quantity'), CartManager::MAX_QUANTITY);

        $reason = $variant->unavailableReason($quantityAfterAdding);

        if ($reason !== null) {
            return back()->withErrors(['quantity' => $reason])->withInput();
        }

        $cart = $this->resolver->resolveForWrite($request);
        $this->manager->addItem($cart, $variant, $request->integer('quantity'), $customization);

        if ($customization !== null) {
            return redirect()->route('cart.show')->with('status', 'محصول با ابعاد واردشده به سبد خرید اضافه شد.');
        }

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
            $variantTotal = $this->manager->quantitiesByVariant($cartItem->cart)[$cartItem->product_variant_id];
            $reason = $cartItem->variant->unavailableReason($variantTotal - $cartItem->quantity + $quantity);

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
