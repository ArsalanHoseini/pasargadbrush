<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductReviewRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class ProductReviewController extends Controller
{
    /**
     * Saved as pending; it appears on the product page once staff approve it.
     */
    public function store(StoreProductReviewRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $product->reviews()->create([
            ...$request->safe()->except('website'),
            'customer_id' => $request->user('customer')?->id,
            'status' => 'pending',
        ]);

        return redirect()->to(route('products.show', $product).'#reviews')->with('review_sent', true);
    }
}
