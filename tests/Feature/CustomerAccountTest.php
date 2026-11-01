<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuoteRequest;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Mail::fake();

        $this->customer = Customer::create(['name' => 'علی رضایی', 'phone' => '09121234567', 'phone_verified_at' => now()]);
    }

    private function order(Customer $customer, string $number): Order
    {
        return $customer->orders()->create([
            'order_number' => $number, 'status' => 'pending', 'payment_status' => 'unpaid',
            'subtotal_rial' => 10, 'total_rial' => 10, 'placed_at' => now(),
        ]);
    }

    private function savedAddress(Customer $customer, array $overrides = []): Address
    {
        return $customer->addresses()->create(array_merge([
            'is_saved' => true, 'label' => 'کارخانه', 'province' => 'فارس', 'city' => 'مرودشت',
            'address_line' => 'شهرک صنعتی', 'recipient_name' => 'علی', 'recipient_phone' => '09121234567',
        ], $overrides));
    }

    public function test_customer_sees_only_their_own_orders(): void
    {
        $mine = $this->order($this->customer, 'PB-MINE-1');
        $theirs = $this->order(Customer::create(['name' => 'دیگری', 'phone' => '09129999999']), 'PB-THEIRS-1');

        $this->actingAs($this->customer, 'customer')->get(route('account.orders.index'))
            ->assertSee('PB-MINE-1')
            ->assertDontSee('PB-THEIRS-1');

        $this->actingAs($this->customer, 'customer')->get(route('account.orders.show', $mine))->assertOk()->assertSee('ثبت شد');
        $this->actingAs($this->customer, 'customer')->get(route('account.orders.show', $theirs))->assertNotFound();
    }

    public function test_dashboard_summarises_open_orders(): void
    {
        $this->order($this->customer, 'PB-OPEN-1');

        $this->actingAs($this->customer, 'customer')->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee('سلام علی رضایی')
            ->assertSee('PB-OPEN-1');
    }

    public function test_quote_requests_include_account_ones_and_earlier_guest_ones_with_the_same_mobile(): void
    {
        QuoteRequest::create(['customer_id' => $this->customer->id, 'name' => 'x', 'phone' => '0711', 'message' => 'در حساب']);
        QuoteRequest::create(['name' => 'x', 'phone' => '09121234567', 'message' => 'مهمان با همین شماره']);
        QuoteRequest::create(['name' => 'x', 'phone' => '09129999999', 'message' => 'شخص دیگر']);

        $this->actingAs($this->customer, 'customer')->get(route('account.quote-requests.index'))
            ->assertSee('در حساب')
            ->assertSee('مهمان با همین شماره')
            ->assertDontSee('شخص دیگر');
    }

    public function test_a_quote_request_sent_while_logged_in_is_prefilled_and_attached_to_the_account(): void
    {
        $product = Product::create(['sku' => 'Q1', 'slug' => 'q1', 'name' => 'برس', 'type' => 'quote', 'is_active' => true]);

        $this->actingAs($this->customer, 'customer')->get(route('products.show', $product))
            ->assertSee('value="علی رضایی"', false);

        $this->actingAs($this->customer, 'customer')->post(route('quote-requests.store'), [
            'product_id' => $product->id, 'name' => 'علی رضایی', 'phone' => '09121234567', 'message' => 'سلام',
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->customer->id, QuoteRequest::firstOrFail()->customer_id);
    }

    public function test_a_guest_quote_request_with_a_mobile_is_stored_in_canonical_form(): void
    {
        $this->post(route('quote-requests.store'), ['name' => 'x', 'phone' => '۰۹۱۲ ۱۲۳ ۴۵۶۷', 'message' => 'سلام']);
        $this->post(route('quote-requests.store'), ['name' => 'y', 'phone' => '071-43334444', 'message' => 'سلام']);

        $this->assertSame(['09121234567', '071-43334444'], QuoteRequest::orderBy('id')->pluck('phone')->all());
    }

    public function test_profile_and_password_can_be_updated(): void
    {
        $this->actingAs($this->customer, 'customer')->put(route('account.profile.update'), [
            'name' => 'علی رضایی', 'company_name' => 'شرکت نمونه', 'email' => 'ali@example.com',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->customer, 'customer')->put(route('account.profile.password'), [
            'password' => 'short', 'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->actingAs($this->customer, 'customer')->put(route('account.profile.password'), [
            'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1',
        ])->assertSessionHasNoErrors();

        $this->customer->refresh();
        $this->assertSame('شرکت نمونه', $this->customer->company_name);
        $this->assertTrue($this->customer->hasPassword());
        $this->assertNotSame('long-enough-1', $this->customer->password);
    }

    public function test_address_book_crud_and_default(): void
    {
        $this->actingAs($this->customer, 'customer')->post(route('account.addresses.store'), [
            'label' => 'دفتر', 'recipient_name' => 'علی', 'recipient_phone' => '۰۹۱۲۱۲۳۴۵۶۷',
            'province' => 'تهران', 'city' => 'تهران', 'address_line' => 'ونک',
        ])->assertRedirect(route('account.addresses.index'));

        $first = Address::firstOrFail();
        $this->assertTrue($first->is_saved);
        $this->assertTrue($first->is_default, 'The first saved address becomes the default.');
        $this->assertSame('09121234567', $first->recipient_phone);

        $second = $this->savedAddress($this->customer);
        $this->actingAs($this->customer, 'customer')->put(route('account.addresses.update', $second), [
            ...$second->only(['label', 'recipient_name', 'recipient_phone', 'province', 'city', 'address_line']),
            'city' => 'شیراز', 'is_default' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('شیراز', $second->refresh()->city);
        $this->assertTrue($second->is_default);
        $this->assertFalse($first->refresh()->is_default);

        $this->actingAs($this->customer, 'customer')->delete(route('account.addresses.destroy', $first));
        $this->assertModelMissing($first);
    }

    public function test_cannot_touch_another_customers_address_or_an_orders_frozen_copy(): void
    {
        $someoneElses = $this->savedAddress(Customer::create(['name' => 'دیگری', 'phone' => '09129999999']));
        $orderCopy = $this->savedAddress($this->customer, ['is_saved' => false]);

        $this->actingAs($this->customer, 'customer')->get(route('account.addresses.edit', $someoneElses))->assertNotFound();
        $this->actingAs($this->customer, 'customer')->delete(route('account.addresses.destroy', $orderCopy))->assertNotFound();
        $this->assertModelExists($orderCopy);
    }

    private function cartToken(): string
    {
        $product = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'محصول', 'type' => 'priced', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'V1', 'label' => 'استاندارد', 'price_rial' => 1_000_000]);
        $response = $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        return collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'cart_token')->getValue();
    }

    public function test_logged_in_checkout_uses_the_account_number_and_a_saved_address(): void
    {
        $saved = $this->savedAddress($this->customer, ['address_line' => 'خیابان کارخانه']);
        $token = $this->cartToken();

        $this->actingAs($this->customer, 'customer')->withUnencryptedCookie('cart_token', $token)
            ->get(route('checkout.create'))
            ->assertSee('خیابان کارخانه')
            ->assertDontSee('name="phone"', false);

        $this->actingAs($this->customer, 'customer')->withUnencryptedCookie('cart_token', $token)
            ->post(route('checkout.store'), ['name' => 'علی رضایی', 'phone' => '09129999999', 'saved_address_id' => $saved->id])
            ->assertRedirect(route('checkout.confirmation'));

        $order = Order::with('shippingAddress')->firstOrFail();
        $this->assertSame('09121234567', $order->customer_phone);
        $this->assertTrue($order->customer->is($this->customer));
        $this->assertSame('خیابان کارخانه', $order->shippingAddress->address_line);
        $this->assertFalse($order->shippingAddress->is_saved, 'The order points at its own frozen copy.');
        $this->assertNotSame($saved->id, $order->shipping_address_id);
    }

    public function test_a_new_address_typed_at_checkout_can_be_saved_to_the_book(): void
    {
        $token = $this->cartToken();

        $this->actingAs($this->customer, 'customer')->withUnencryptedCookie('cart_token', $token)
            ->post(route('checkout.store'), [
                'name' => 'علی رضایی', 'province' => 'فارس', 'city' => 'شیراز', 'address_line' => 'آدرس جدید', 'save_address' => '1',
            ])->assertRedirect(route('checkout.confirmation'));

        $this->assertSame(['آدرس جدید'], $this->customer->savedAddresses()->pluck('address_line')->all());
        $this->assertTrue($this->customer->savedAddresses()->first()->is_default);
    }

    public function test_someone_elses_saved_address_id_is_ignored_at_checkout(): void
    {
        $someoneElses = $this->savedAddress(Customer::create(['name' => 'دیگری', 'phone' => '09129999999']));
        $token = $this->cartToken();

        $this->actingAs($this->customer, 'customer')->withUnencryptedCookie('cart_token', $token)
            ->post(route('checkout.store'), ['name' => 'علی', 'saved_address_id' => $someoneElses->id])
            ->assertSessionHasErrors(['province', 'city', 'address_line']);
    }

    public function test_editing_a_saved_address_never_changes_a_past_order(): void
    {
        $saved = $this->savedAddress($this->customer, ['address_line' => 'آدرس قدیم']);
        $token = $this->cartToken();
        $this->actingAs($this->customer, 'customer')->withUnencryptedCookie('cart_token', $token)
            ->post(route('checkout.store'), ['name' => 'علی', 'saved_address_id' => $saved->id]);

        $saved->update(['address_line' => 'آدرس جدید']);

        $this->assertSame('آدرس قدیم', Order::firstOrFail()->shippingAddress->address_line);
    }
}
