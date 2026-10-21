<?php

namespace App\Services\Cart;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Resolves the current visitor's cart via a long-lived cookie — independent
 * of login/session, so a guest's cart survives across visits. A cart row is
 * only ever created when something is actually about to be written to it;
 * merely browsing never creates one (see resolveForDisplay()).
 */
class CartResolver
{
    public const COOKIE = 'cart_token';

    public function resolveForWrite(Request $request): Cart
    {
        $token = $request->cookie(self::COOKIE);

        if ($token) {
            $cart = Cart::query()->where('session_token', $token)->first();
            if ($cart) {
                return $cart;
            }
        }

        $token = Str::random(40);
        Cookie::queue(Cookie::make(self::COOKIE, $token, 60 * 24 * 30));

        return Cart::create(['session_token' => $token]);
    }

    public function resolveForDisplay(Request $request): ?Cart
    {
        $token = $request->cookie(self::COOKIE);

        if (! $token) {
            return null;
        }

        return Cart::query()->where('session_token', $token)->with('items.variant.product')->first();
    }
}
