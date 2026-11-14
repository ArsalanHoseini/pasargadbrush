<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\PersianDigits;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim(PersianDigits::toLatin((string) $request->query('q', '')));

        $customers = Customer::query()
            ->withCount('orders')
            ->withSum(['orders as orders_total_rial' => fn ($query) => $query->where('status', '!=', 'cancelled')], 'total_rial')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when($request->query('has_orders') === 'yes', fn ($query) => $query->has('orders'))
            ->when($request->query('has_orders') === 'no', fn ($query) => $query->doesntHave('orders'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', ['customers' => $customers]);
    }

    public function show(Customer $customer): View
    {
        return view('admin.customers.show', [
            'customer' => $customer,
            'orders' => $customer->orders()->latest()->get(),
            'addresses' => $customer->addresses,
        ]);
    }
}
