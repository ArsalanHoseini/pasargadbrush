<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackOrderRequest;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lets a guest customer (there are no customer accounts) look up their own
 * order with the order number + the mobile number it was placed with. Both
 * must match, and the lookup is throttled, so order numbers alone can't be
 * enumerated. The found order is remembered in the session rather than put
 * in the URL, so the details never end up in a shareable link or log.
 */
class OrderTrackingController extends Controller
{
    private const SESSION_KEY = 'tracked_order_id';

    public function create(): View
    {
        return view('orders.track');
    }

    public function store(TrackOrderRequest $request): RedirectResponse
    {
        $phone = $request->validated('phone');

        $order = Order::query()
            ->where('order_number', $request->validated('order_number'))
            ->where(fn (Builder $query) => $query
                ->where('customer_phone', $phone)
                ->orWhere(fn (Builder $legacy) => $legacy
                    ->whereNull('customer_phone')
                    ->whereHas('customer', fn (Builder $customer) => $customer->where('phone', $phone))))
            ->first();

        if (! $order) {
            return back()->withInput()->withErrors([
                'order_number' => 'سفارشی با این شماره سفارش و شماره موبایل پیدا نشد. لطفاً هر دو را دوباره بررسی کنید.',
            ]);
        }

        $request->session()->put(self::SESSION_KEY, $order->id);

        return redirect()->route('orders.track.show');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $order = Order::with('items', 'shippingAddress')->find($request->session()->get(self::SESSION_KEY));

        if (! $order) {
            return redirect()->route('orders.track.create');
        }

        return view('orders.status', ['order' => $order]);
    }
}
