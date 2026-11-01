<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDimensionField;
use App\Models\ProductVariant;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ReorderTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Product $product;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);

        $this->customer = Customer::create(['name' => 'علی', 'phone' => '09121234567']);
        $this->product = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'برس', 'type' => 'priced', 'is_active' => true]);
        $this->variant = ProductVariant::create(['product_id' => $this->product->id, 'sku' => 'V1', 'label' => 'بزرگ', 'price_rial' => 1_000_000]);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function order(array $lines, ?Customer $customer = null): Order
    {
        $order = ($customer ?? $this->customer)->orders()->create([
            'order_number' => 'PB-'.uniqid(), 'status' => 'completed', 'payment_status' => 'paid',
            'subtotal_rial' => 1, 'total_rial' => 1, 'placed_at' => now(),
        ]);

        foreach ($lines as $line) {
            $order->items()->create(array_merge([
                'product_id' => $this->product->id, 'product_variant_id' => $this->variant->id,
                'name_snapshot' => 'برس — بزرگ', 'sku_snapshot' => 'V1',
                'unit_price_rial' => 800_000, 'quantity' => 2, 'line_total_rial' => 1_600_000,
            ], $line));
        }

        return $order;
    }

    private function reorder(Order $order): TestResponse
    {
        return $this->actingAs($this->customer, 'customer')->post(route('account.orders.reorder', $order));
    }

    public function test_the_order_page_offers_reorder(): void
    {
        $order = $this->order([[]]);

        $this->actingAs($this->customer, 'customer')->get(route('account.orders.show', $order))
            ->assertSee(route('account.orders.reorder', $order));
    }

    public function test_reordering_fills_the_cart_at_todays_price(): void
    {
        $order = $this->order([[]]);

        $this->reorder($order)
            ->assertRedirect(route('cart.show'))
            ->assertSessionHas('status', fn ($status) => str_contains($status, '1 قلم'))
            ->assertCookie('cart_token');

        $item = CartItem::firstOrFail();
        $this->assertSame(2, $item->quantity);
        $this->assertSame($this->variant->id, $item->product_variant_id);
        $this->assertNull($item->customization);
    }

    public function test_reordering_twice_merges_plain_lines(): void
    {
        $order = $this->order([[]]);

        $token = collect($this->reorder($order)->headers->getCookies())->first(fn ($c) => $c->getName() === 'cart_token')->getValue();
        $this->actingAs($this->customer, 'customer')->withUnencryptedCookie('cart_token', $token)->post(route('account.orders.reorder', $order));

        $this->assertSame(1, CartItem::count());
        $this->assertSame(4, CartItem::firstOrFail()->quantity);
    }

    public function test_custom_dimensions_are_carried_over_while_they_still_fit_the_product(): void
    {
        $field = ProductDimensionField::factory()->create(['product_id' => $this->product->id, 'code' => 'D', 'label' => 'قطر', 'max_value' => 500]);
        $order = $this->order([[
            'customization' => ['dimensions' => [['code' => 'D', 'label' => 'قطر', 'unit' => 'mm', 'value' => '300']], 'notes' => 'موی سیمی'],
        ]]);

        $this->reorder($order);

        $customization = CartItem::firstOrFail()->customization;
        $this->assertSame('300', $customization['dimensions'][0]['value']);
        $this->assertSame('موی سیمی', $customization['notes']);

        CartItem::query()->delete();
        $field->update(['max_value' => 200]);

        $this->reorder($order)->assertSessionHas('reorder_skipped', fn ($skipped) => str_contains($skipped[0]['reason'], 'فرم ابعاد'));
        $this->assertSame(0, CartItem::count());
    }

    public function test_lines_that_cannot_be_ordered_online_are_skipped_with_a_reason(): void
    {
        $quoteOnly = Product::create(['sku' => 'Q1', 'slug' => 'q1', 'name' => 'برس دیسکی', 'type' => 'quote']);
        $order = $this->order([
            [],
            ['product_id' => $quoteOnly->id, 'product_variant_id' => null, 'name_snapshot' => 'برس دیسکی'],
        ]);

        $this->reorder($order)->assertSessionHas('reorder_skipped', [
            ['name' => 'برس دیسکی', 'reason' => 'این قلم به‌صورت آنلاین قابل سفارش نیست — برای آن استعلام قیمت بفرستید یا به کارشناس پیام دهید.'],
        ]);
        $this->assertSame(1, CartItem::count());

        $this->followingRedirects()->actingAs($this->customer, 'customer')->post(route('account.orders.reorder', $order))
            ->assertSee('این اقلام به سبد اضافه نشدند');
    }

    public function test_deactivated_or_out_of_stock_products_are_skipped(): void
    {
        $order = $this->order([[]]);

        $this->variant->update(['stock_quantity' => 1]);
        $this->reorder($order)->assertSessionHas('reorder_skipped', fn ($skipped) => str_contains($skipped[0]['reason'], 'فقط 1 عدد'));

        $this->product->update(['is_active' => false]);
        $this->reorder($order)->assertSessionHas('reorder_skipped', fn ($skipped) => str_contains($skipped[0]['reason'], 'قابل خرید نیست'));

        $this->assertSame(0, CartItem::count());
    }

    public function test_cannot_reorder_someone_elses_order(): void
    {
        $theirs = $this->order([[]], Customer::create(['name' => 'دیگری', 'phone' => '09129999999']));

        $this->post(route('account.orders.reorder', $theirs))->assertRedirect(route('account.login'));
        $this->reorder($theirs)->assertNotFound();
        $this->assertSame(0, CartItem::count());
    }
}
