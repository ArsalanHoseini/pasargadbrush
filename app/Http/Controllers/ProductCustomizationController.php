<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart\CartManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The step between "افزودن به سبد" and the cart for a product with a
 * dimensions form: the customer sees the schematic and enters each
 * dimension; the form then posts to the normal add-to-cart endpoint, which
 * validates the dimensions again (CartController::store()).
 */
class ProductCustomizationController extends Controller
{
    public function create(Request $request, Product $product): View|RedirectResponse
    {
        $product->load(['dimensionFields', 'schematic', 'variants']);

        $variant = $product->variants->firstWhere('id', $request->integer('variant'));

        if (! $product->is_active || $product->type !== 'priced' || ! $product->requiresDimensions() || ! $variant) {
            return redirect()->route('products.show', $product);
        }

        $variant->setRelation('product', $product);

        return view('products.customize', [
            'product' => $product,
            'variant' => $variant,
            'quantity' => max(1, min($request->integer('quantity', 1), CartManager::MAX_QUANTITY)),
        ]);
    }
}
