<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Customer;
use App\Support\IranianMobile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * The customer's address book. Orders never point at these rows (each order
 * gets its own frozen copy), so they can be edited and deleted freely.
 */
class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.addresses.index', ['addresses' => $this->customer($request)->savedAddresses]);
    }

    public function create(Request $request): View
    {
        $customer = $this->customer($request);

        return view('account.addresses.form', ['address' => new Address([
            'recipient_name' => $customer->name,
            'recipient_phone' => $customer->phone,
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = $this->customer($request);

        DB::transaction(function () use ($request, $customer) {
            $address = $customer->addresses()->create([...$this->validated($request), 'is_saved' => true]);

            if ($request->boolean('is_default') || $customer->savedAddresses()->count() === 1) {
                $this->makeDefault($customer, $address);
            }
        });

        return redirect()->route('account.addresses.index')->with('status', 'آدرس ذخیره شد.');
    }

    public function edit(Request $request, Address $address): View
    {
        return view('account.addresses.form', ['address' => $this->owned($request, $address)]);
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $address = $this->owned($request, $address);

        DB::transaction(function () use ($request, $address) {
            $address->update($this->validated($request));

            if ($request->boolean('is_default')) {
                $this->makeDefault($this->customer($request), $address);
            }
        });

        return redirect()->route('account.addresses.index')->with('status', 'آدرس به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->owned($request, $address)->delete();

        return redirect()->route('account.addresses.index')->with('status', 'آدرس حذف شد.');
    }

    private function customer(Request $request): Customer
    {
        return $request->user('customer');
    }

    /**
     * Only the customer's own address-book rows — never someone else's, and
     * never an order's frozen copy.
     */
    private function owned(Request $request, Address $address): Address
    {
        abort_unless($address->customer_id === $this->customer($request)->id && $address->is_saved, 404);

        return $address;
    }

    private function makeDefault(Customer $customer, Address $address): void
    {
        $customer->addresses()->where('is_saved', true)->whereKeyNot($address->id)->update(['is_default' => false]);
        $address->update(['is_default' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        if (is_string($request->input('recipient_phone'))) {
            $request->merge(['recipient_phone' => IranianMobile::normalize($request->input('recipient_phone'))]);
        }

        return $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:20'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address_line' => ['required', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:20'],
        ], attributes: [
            'label' => 'عنوان آدرس',
            'recipient_name' => 'نام گیرنده',
            'recipient_phone' => 'تلفن گیرنده',
        ]);
    }
}
