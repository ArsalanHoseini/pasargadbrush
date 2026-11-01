<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartItemRequest;
use App\Models\CartItem;
use App\Models\Product;
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
            'priceNeedsConfirmation' => $cart && $this->manager->priceNeedsConfirmation($cart),
            'problems' => $cart ? $this->manager->problems($cart) : [],
        ]);
    }

    /**
     * Adds a priced product's variant, or a quote-only product (no variant —
     * staff price it after the order), with the product's order form
     * (dimensions/choices) validated when it has one.
     */
    public function store(AddCartItemRequest $request, DimensionForm $dimensionForm)
    {
        $variant = $request->filled('product_variant_id')
            ? ProductVariant::findOrFail($request->integer('product_variant_id'))
            : null;
        $product = Product::with('dimensionFields')->findOrFail($variant?->product_id ?? $request->integer('product_id'));

        abort_unless($product->is_active, 404);
        // A variant only ever belongs to a priced product; a priced product needs one.
        abort_if($variant !== null && $product->type !== 'priced', 404);

        if ($product->type === 'priced' && $variant === null) {
            return back()->withErrors(['product_variant_id' => 'لطفاً یکی از گزینه‌های محصول را انتخاب کنید.'])->withInput();
        }

        $customization = null;

        if ($product->requiresDimensions()) {
            $dimensionForm->normalize($request, '', $product);
            $validated = $request->validate(
                $dimensionForm->rules($product),
                attributes: $dimensionForm->attributes($product),
            );
            $customization = $dimensionForm->snapshot($product, $validated);
        }

        $reason = $variant
            ? $variant->unavailableReason($this->quantityAfterAdding($request, $variant))
            : $product->quoteOrderUnavailableReason();

        if ($reason !== null) {
            return back()->withErrors(['quantity' => $reason])->withInput();
        }

        $cart = $this->resolver->resolveForWrite($request);
        $this->manager->addItem($cart, $product, $variant, $request->integer('quantity'), $customization);

        return redirect()->route('cart.show')->with('status', $customization !== null
            ? 'محصول با مشخصات واردشده به سبد خرید اضافه شد.'
            : 'به سبد خرید اضافه شد.');
    }

    private function quantityAfterAdding(Request $request, ProductVariant $variant): int
    {
        $existingCart = $this->resolver->resolveForDisplay($request);

        return $existingCart
            ? $this->manager->quantityAfterAdding($existingCart, $variant, $request->integer('quantity'))
            : min($request->integer('quantity'), CartManager::MAX_QUANTITY);
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $this->authorizeCartItem($request, $cartItem);

        $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.CartManager::MAX_QUANTITY]]);
        $quantity = $request->integer('quantity');

        // Lowering a quantity or removing a line is always allowed, even for
        // an item that has since become unavailable — only increases are checked.
        if ($quantity > $cartItem->quantity) {
            $quantityToCheck = $cartItem->product_variant_id
                ? $this->manager->quantitiesByVariant($cartItem->cart)[$cartItem->product_variant_id] - $cartItem->quantity + $quantity
                : $quantity;
            $reason = $cartItem->unavailableReason($quantityToCheck);

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
