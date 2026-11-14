<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManualOrderRequest;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Services\Cart\CartManager;
use App\Services\Checkout\CreateManualOrder;
use App\Services\Checkout\NotifyOrderPlaced;
use App\Services\Checkout\NotifyOrderUpdated;
use App\Services\Checkout\OrderStock;
use App\Services\Checkout\ShippingCalculator;
use App\Services\Checkout\ShippingMethod;
use App\Services\Customization\DimensionForm;
use App\Support\PersianDigits;
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
        $search = trim(PersianDigits::toLatin((string) $request->query('q', '')));

        $orders = Order::query()
            ->with('customer')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%")
                ->orWhere('shipment_tracking_code', 'like', "%{$search}%")))
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->query('payment_status')))
            ->when($request->filled('source'), fn ($query) => $query->where('source', $request->query('source')))
            ->when($request->boolean('price_confirmation'), fn ($query) => $query
                ->where('requires_price_confirmation', true)
                ->whereNotIn('status', ['cancelled', 'completed']))
            ->latest('placed_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'status' => $status,
            'statusCounts' => Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /**
     * Manual order entry; with ?quote_request={id} the form is pre-filled
     * from that request (customer, product, dimensions).
     */
    public function create(Request $request, ShippingCalculator $shipping, DimensionForm $dimensionForm): View|RedirectResponse
    {
        $quoteRequest = $request->filled('quote_request')
            ? QuoteRequest::with('product.dimensionFields')->findOrFail($request->integer('quote_request'))
            : null;

        if ($quoteRequest?->order_id) {
            return redirect()->route('admin.orders.edit', $quoteRequest->order_id)
                ->with('status', 'برای این درخواست قبلاً سفارش ثبت شده است.');
        }

        $products = Product::query()
            ->with(['variants' => fn ($query) => $query->orderBy('sort_order'), 'dimensionFields', 'schematics'])
            ->orderBy('name')
            ->get();

        return view('admin.orders.create', [
            'quoteRequest' => $quoteRequest,
            'products' => $products,
            'defaultShippingMethod' => $shipping->currentMethod(),
            'prefill' => $this->prefillFromQuoteRequest($quoteRequest, $dimensionForm),
        ]);
    }

    public function store(StoreManualOrderRequest $request, CreateManualOrder $createManualOrder, NotifyOrderPlaced $notifyOrderPlaced): RedirectResponse
    {
        $order = $createManualOrder->handle($request->validated(), $request->products(), $request->user());

        if ($request->boolean('notify_customer')) {
            $notifyOrderPlaced->notifyCustomer($order);
        }

        return redirect()->route('admin.orders.edit', $order)->with('status', 'سفارش '.$order->order_number.' ثبت شد.');
    }

    public function edit(Order $order): View
    {
        return view('admin.orders.edit', [
            'order' => $order->load(['customer', 'shippingAddress', 'items', 'payments', 'conversation']),
            'shipping' => $order->shippingQuote(),
        ]);
    }

    public function update(Request $request, Order $order, OrderStock $orderStock, NotifyOrderUpdated $notifyOrderUpdated): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUS_LABELS))],
            'shipment_tracking_code' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['required', Rule::in(array_keys(Order::PAYMENT_STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shipping_cost_toman' => ['nullable', 'integer', 'min:0'],
            'item_prices' => ['nullable', 'array'],
            'item_prices.*' => ['nullable', 'integer', 'min:0'],
            'price_confirmed' => ['nullable', 'boolean'],
            'notify_customer' => ['nullable', 'boolean'],
        ], attributes: ['shipping_cost_toman' => 'هزینه ارسال', 'item_prices.*' => 'قیمت واحد']);

        $itemPrices = $data['item_prices'] ?? [];
        $priceConfirmed = (bool) ($data['price_confirmed'] ?? false);
        $notifyCustomer = (bool) ($data['notify_customer'] ?? false);
        unset($data['item_prices'], $data['price_confirmed'], $data['notify_customer']);

        $previousStatus = $order->status;
        $wasAwaitingPrice = $order->requires_price_confirmation;

        [$data, $lineUpdates] = $this->withConfirmedPrices($order, $data, $itemPrices, $priceConfirmed);
        $data = $this->withShippingCost($order, $data);

        $changes = array_diff_assoc($data, $order->only(array_keys($data)));

        if ($lineUpdates !== []) {
            $changes['item_prices_rial'] = array_map(fn (array $line) => $line['unit_price_rial'], $lineUpdates);
        }

        $isBeingCancelled = $order->status !== 'cancelled' && $data['status'] === 'cancelled';
        $isBeingUncancelled = $order->status === 'cancelled' && $data['status'] !== 'cancelled';

        $shortages = DB::transaction(function () use ($order, $data, $lineUpdates, $isBeingCancelled, $isBeingUncancelled, $orderStock) {
            if ($isBeingCancelled) {
                $orderStock->release($order);
            }

            if ($isBeingUncancelled) {
                $shortages = $orderStock->reserve($order);

                if ($shortages !== []) {
                    return $shortages;
                }
            }

            foreach ($lineUpdates as $itemId => $line) {
                $order->items()->whereKey($itemId)->update($line);
            }

            $order->update($data);

            return [];
        });

        if ($shortages !== []) {
            return back()->withInput()->withErrors([
                'status' => 'موجودی کافی برای فعال‌کردن دوباره‌ی این سفارش وجود ندارد: '.implode('، ', $shortages),
            ]);
        }

        $customerNotified = $notifyCustomer && $notifyOrderUpdated->handle(
            $order->refresh(),
            statusChanged: $order->status !== $previousStatus,
            priceConfirmed: $wasAwaitingPrice && ! $order->requires_price_confirmation,
        );

        if ($customerNotified) {
            $changes['customer_notified'] = true;
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

        return redirect()->route('admin.orders.index')->with('status', $customerNotified
            ? 'سفارش به‌روزرسانی شد و به مشتری پیامک/ایمیل ارسال شد.'
            : 'سفارش به‌روزرسانی شد.');
    }

    /**
     * @return array{name: string, phone: string, email: string, notes: string, items: list<array<string, mixed>>}
     */
    private function prefillFromQuoteRequest(?QuoteRequest $quoteRequest, DimensionForm $dimensionForm): array
    {
        if (! $quoteRequest) {
            return ['name' => '', 'phone' => '', 'email' => '', 'notes' => '', 'items' => []];
        }

        // "requested_quantity" is free text (e.g. «۵۰۰ عدد»); use its first number.
        preg_match('/\d+/', PersianDigits::toLatin((string) $quoteRequest->requested_quantity), $quantityMatch);

        return [
            'name' => $quoteRequest->name,
            'phone' => $quoteRequest->phone,
            'email' => (string) $quoteRequest->email,
            'notes' => 'ثبت‌شده از درخواست استعلام #'.$quoteRequest->id.($quoteRequest->company ? ' — '.$quoteRequest->company : ''),
            'items' => $quoteRequest->product ? [[
                'product_id' => $quoteRequest->product->id,
                'product_variant_id' => '',
                'name' => '',
                'quantity' => max(1, min((int) ($quantityMatch[0] ?? 1), CartManager::MAX_QUANTITY)),
                'unit_price_toman' => '',
                'dimensions' => (object) $dimensionForm->valuesFromSnapshot($quoteRequest->product, $quoteRequest->customization),
                'customization_notes' => $quoteRequest->customization['notes'] ?? '',
            ]] : [],
        ];
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
        $data['total_rial'] = ($data['subtotal_rial'] ?? $order->subtotal_rial) + $data['shipping_cost_rial'];

        return $data;
    }

    /**
     * While an order with custom-dimension lines awaits price confirmation,
     * staff can set each line's final unit price (Toman); line totals,
     * subtotal and total follow. Ticking "price confirmed" clears the flag,
     * after which prices are no longer editable here.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int|string, int|null>  $itemPrices  Unit prices in Toman, keyed by order item id.
     * @return array{0: array<string, mixed>, 1: array<int, array{unit_price_rial: int, line_total_rial: int}>}
     */
    private function withConfirmedPrices(Order $order, array $data, array $itemPrices, bool $priceConfirmed): array
    {
        if (! $order->requires_price_confirmation) {
            return [$data, []];
        }

        $lineUpdates = [];
        $subtotal = 0;

        foreach ($order->items as $item) {
            $unitPrice = isset($itemPrices[$item->id]) ? (int) $itemPrices[$item->id] * 10 : $item->unit_price_rial;
            $lineTotal = $unitPrice * $item->quantity;
            $subtotal += $lineTotal;

            if ($unitPrice !== $item->unit_price_rial) {
                $lineUpdates[$item->id] = ['unit_price_rial' => $unitPrice, 'line_total_rial' => $lineTotal];
            }
        }

        if ($subtotal !== $order->subtotal_rial) {
            $data['subtotal_rial'] = $subtotal;
            $data['total_rial'] = $subtotal + $order->shipping_cost_rial;
        }

        if ($priceConfirmed) {
            $data['requires_price_confirmation'] = false;
        }

        return [$data, $lineUpdates];
    }
}
