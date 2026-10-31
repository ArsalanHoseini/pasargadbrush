<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Services\Checkout\OrderStock;
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
        ]);
    }

    public function update(Request $request, Order $order, OrderStock $orderStock): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'processing', 'shipped', 'completed', 'cancelled'])],
            'payment_status' => ['required', Rule::in(['unpaid', 'paid', 'failed', 'refunded'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

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
}
