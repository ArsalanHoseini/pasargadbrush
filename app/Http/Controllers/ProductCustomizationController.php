<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;

/**
 * The order form now lives on the product page itself; this old separate
 * "customize" step only redirects there, so earlier links keep working.
 */
class ProductCustomizationController extends Controller
{
    public function create(Product $product): RedirectResponse
    {
        return redirect()->route('products.show', $product);
    }
}
