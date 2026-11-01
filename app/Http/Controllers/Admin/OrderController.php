<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Services\Checkout\OrderStock;
use App\Services\Checkout\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $orders = Order::query()
            ->with('customer')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', ['orders' => $orders, 'status' => $status]);
    }

    public function edit(Order $order): View
    {
        return view('admin.orders.edit', [
            'order' => $order->load(['customer', 'shippingAddress', 'items', 'payments']),
            'shipping' => $order->shippingQuote(),
        ]);
    }

    public function update(Request $request, Order $order, OrderStock $orderStock): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUS_LABELS))],
            'shipment_tracking_code' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shipping_cost_toman' => ['nullable', 'integer', 'min:0'],
        ], attributes: ['shipping_cost_toman' => 'هزینه ارسال']);

        $data = $this->withShippingCost($order, $data);

        $changes = array_diff_assoc($data, $order->only(array_keys($data)));

        $isBeingCancelled = $order->status !== 'cancelled' && $data['status'] === 'cancelled';
        $isBeingUncancelled = $order->status === 'cancelled' && $data['status'] !== 'cancelled';

        $shortages = DB::transaction(function () use ($order, $data, $isBeingCancelled, $isBeingUncancelled, $orderStock) {
            if ($isBeingCancelled) {
                $orderStock->release($order);
            }

            if ($isBeingUncancelled) {
                $shortages = $orderStock->reserve($order);

                if ($shortages !== []) {
                    return $shortages;
                }
            }

            $order->update($data);

            return [];
        });

        if ($shortages !== []) {
            return back()->withInput()->withErrors([
                'status' => 'موجودی کافی برای فعال‌کردن دوباره‌ی این سفارش وجود ندارد: '.implode('، ', $shortages),
            ]);
        }

        if (! empty($changes)) {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'order.updated',
                'auditable_type' => Order::class,
                'auditable_id' => $order->id,
                'changes' => $changes,
                'ip_address' => $request->ip(),
            ]);
        }

        return redirect()->route('admin.orders.index')->with('status', 'سفارش به‌روزرسانی شد.');
    }

    /**
     * Staff enter the agreed shipping amount (Toman) on "announced" orders,
     * or correct it on flat-rate ones; the total always follows. Cash-on-
     * delivery orders are never charged shipping by the shop, so the field
     * is ignored for them.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withShippingCost(Order $order, array $data): array
    {
        $shippingCostToman = $data['shipping_cost_toman'] ?? null;
        unset($data['shipping_cost_toman']);

        if ($shippingCostToman === null || $order->shippingQuote()->method === ShippingMethod::CashOnDelivery) {
            return $data;
        }

        $data['shipping_cost_rial'] = $shippingCostToman * 10;
        $data['total_rial'] = $order->subtotal_rial + $data['shipping_cost_rial'];

        return $data;
    }
}
