<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\CsvExport;
use App\Support\PersianDate;
use App\Support\PersianDigits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.customers.index', [
            'customers' => $this->filteredCustomers($request)->paginate(20)->withQueryString(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        return CsvExport::download('customers', [
            'نام', 'شرکت', 'موبایل', 'ایمیل', 'تعداد سفارش', 'مجموع خرید (تومان)', 'تاریخ عضویت',
        ], $this->filteredCustomers($request)->lazy(500)->map(fn (Customer $customer) => [
            $customer->name,
            $customer->company_name,
            $customer->phone,
            $customer->email,
            $customer->orders_count,
            intdiv((int) $customer->orders_total_rial, 10),
            PersianDate::format($customer->created_at, PersianDate::COMPACT_DATE),
        ]));
    }

    public function show(Customer $customer): View
    {
        $orders = $customer->orders()->latest('placed_at')->get();
        $countedOrders = $orders->where('status', '!=', 'cancelled');

        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $orders,
            'addresses' => $customer->addresses,
            'quoteRequests' => $customer->quoteRequests()->with('product')->latest()->limit(10)->get(),
            'stats' => [
                'order_count' => $countedOrders->count(),
                'total_rial' => (int) $countedOrders->sum('total_rial'),
                'average_rial' => $countedOrders->isEmpty() ? 0 : intdiv((int) $countedOrders->sum('total_rial'), $countedOrders->count()),
                'last_order_at' => $orders->first()?->placed_at,
                'unpaid_count' => $countedOrders->where('payment_status', '!=', 'paid')->count(),
            ],
        ]);
    }

    /**
     * Totals count non-cancelled orders only, like the dashboard.
     *
     * @return Builder<Customer>
     */
    private function filteredCustomers(Request $request): Builder
    {
        $search = trim(PersianDigits::toLatin((string) $request->query('q', '')));
        $from = PersianDate::parse($request->query('from'));
        $to = PersianDate::parse($request->query('to'));

        return Customer::query()
            ->withCount('orders')
            ->withSum(['orders as orders_total_rial' => fn ($query) => $query->where('status', '!=', 'cancelled')], 'total_rial')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($request->query('has_orders') === 'yes', fn ($query) => $query->has('orders'))
            ->when($request->query('has_orders') === 'no', fn ($query) => $query->doesntHave('orders'))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<', $to->modify('+1 day')))
            ->latest()
            ->latest('id');
    }
}
