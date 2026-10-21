<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(): View
    {
        $customers = Customer::query()
            ->withCount('orders')
            ->latest()
            ->paginate(20);

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
