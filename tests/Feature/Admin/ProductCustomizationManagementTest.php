<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDimensionField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCustomizationManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'برس دیسکی', 'sku' => 'DISK-1', 'type' => 'quote',
        ], $overrides);
    }

    public function test_staff_define_dimensions_a_choice_and_several_schematics_for_a_product(): void
    {
        Storage::fake('public');

        $this->actingAs($this->staff())->post(route('admin.products.store'), $this->productPayload([
            'schematics' => [
                UploadedFile::fake()->image('front.png', 600, 400),
                UploadedFile::fake()->image('section.png', 600, 400),
            ],
            'dimension_fields' => [
                ['code' => 'E', 'label' => 'قطر خارجی', 'unit' => 'mm', 'is_required' => '1', 'min_value' => '50', 'max_value' => '600'],
                ['code' => 'B', 'label' => 'قطر سوراخ', 'unit' => 'mm', 'is_required' => '0'],
                ['type' => 'choice', 'code' => '', 'label' => 'جنس الیاف', 'options' => "نایلون (PA)\nPET، PP\n\nسایر", 'is_required' => '1'],
            ],
        ]))->assertSessionHasNoErrors();

        $product = Product::with('dimensionFields', 'schematics')->firstOrFail();

        $this->assertCount(2, $product->schematics);
        Storage::disk('public')->assertExists($product->schematics[0]->path);
        $this->assertSame(['E', 'B', ''], $product->dimensionFields->pluck('code')->all());
        $this->assertTrue($product->dimensionFields[0]->is_required);
        $this->assertFalse($product->dimensionFields[1]->is_required);
        $this->assertSame(600.0, $product->dimensionFields[0]->max_value);
        $this->assertTrue($product->dimensionFields[2]->isChoice());
        $this->assertSame(['نایلون (PA)', 'PET', 'PP', 'سایر'], $product->dimensionFields[2]->options);
    }

    public function test_a_choice_needs_options(): void
    {
        $this->actingAs($this->staff())->post(route('admin.products.store'), $this->productPayload([
            'dimension_fields' => [['type' => 'choice', 'label' => 'جنس الیاف', 'options' => '']],
        ]))->assertSessionHasErrors('dimension_fields.0.options');
    }

    public function test_edit_page_lists_the_existing_dimensions(): void
    {
        $product = Product::create(['sku' => 'DISK-1', 'slug' => 'disk', 'name' => 'برس دیسکی', 'type' => 'quote']);
        ProductDimensionField::factory()->create(['product_id' => $product->id, 'code' => 'O.H.', 'label' => 'ارتفاع کل']);

        $this->actingAs($this->staff())->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('سفارشی‌سازی — شماتیک‌ها و فرم سفارش')
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('O.H.')
            // Rendered into the Alpine x-data JSON, where json_encode() escapes non-ASCII.
            ->assertSee(trim(json_encode('ارتفاع کل'), '"'), false);
    }

    public function test_updating_keeps_edited_fields_and_removes_deleted_ones(): void
    {
        $product = Product::create(['sku' => 'DISK-1', 'slug' => 'disk', 'name' => 'برس دیسکی', 'type' => 'quote']);
        $keep = ProductDimensionField::factory()->create(['product_id' => $product->id, 'code' => 'A']);
        $remove = ProductDimensionField::factory()->create(['product_id' => $product->id, 'code' => 'B']);

        $this->actingAs($this->staff())->put(route('admin.products.update', $product), $this->productPayload([
            'dimension_fields' => [
                ['id' => $keep->id, 'code' => 'A', 'label' => 'ارتفاع کل', 'unit' => 'cm', 'is_required' => '1'],
                ['code' => 'C', 'label' => 'عرض', 'unit' => 'mm', 'is_required' => '1'],
            ],
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_dimension_fields', ['id' => $keep->id, 'label' => 'ارتفاع کل', 'unit' => 'cm']);
        $this->assertDatabaseMissing('product_dimension_fields', ['id' => $remove->id]);
        $this->assertSame(['A', 'C'], $product->dimensionFields()->pluck('code')->all());
    }

    public function test_a_field_whose_minimum_exceeds_its_maximum_is_rejected(): void
    {
        $this->actingAs($this->staff())->post(route('admin.products.store'), $this->productPayload([
            'dimension_fields' => [['code' => 'A', 'label' => 'طول', 'unit' => 'mm', 'min_value' => '100', 'max_value' => '10']],
        ]))->assertSessionHasErrors('dimension_fields.0.max_value');
    }

    public function test_two_fields_with_the_same_letter_are_rejected(): void
    {
        $this->actingAs($this->staff())->post(route('admin.products.store'), $this->productPayload([
            'dimension_fields' => [
                ['code' => 'A', 'label' => 'طول', 'unit' => 'mm'],
                ['code' => 'A', 'label' => 'عرض', 'unit' => 'mm'],
            ],
        ]))->assertSessionHasErrors('dimension_fields.1.code');
    }

    public function test_staff_can_add_more_schematics_and_remove_one(): void
    {
        Storage::fake('public');
        $staff = $this->staff();

        $this->actingAs($staff)->post(route('admin.products.store'), $this->productPayload([
            'schematics' => [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.png')],
        ]));
        $product = Product::firstOrFail();
        [$first, $second] = $product->schematics->all();

        $this->actingAs($staff)->put(route('admin.products.update', $product), $this->productPayload([
            'remove_schematic_ids' => [$first->id],
            'schematics' => [UploadedFile::fake()->image('c.png')],
        ]))->assertSessionHasNoErrors();

        $remaining = $product->schematics()->get();
        $this->assertCount(2, $remaining);
        $this->assertTrue($remaining[0]->is($second), 'New schematics are appended after the existing ones.');
    }

    private function orderAwaitingPriceConfirmation(): Order
    {
        $product = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'برس', 'type' => 'priced', 'is_active' => true]);
        $customer = Customer::create(['name' => 'علی', 'phone' => '09120000000']);

        $order = Order::create([
            'order_number' => 'PB-TEST-1', 'customer_id' => $customer->id, 'status' => 'pending', 'payment_status' => 'unpaid',
            'shipping_method' => 'flat', 'subtotal_rial' => 2_000_000, 'shipping_cost_rial' => 500_000, 'total_rial' => 2_500_000,
            'requires_price_confirmation' => true, 'placed_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id, 'name_snapshot' => 'برس', 'sku_snapshot' => 'V1', 'unit_price_rial' => 1_000_000,
            'quantity' => 2, 'line_total_rial' => 2_000_000,
            'customization' => ['dimensions' => [['code' => 'D', 'label' => 'قطر خارجی', 'unit' => 'mm', 'value' => '120']], 'notes' => null],
        ]);

        return $order;
    }

    public function test_order_page_shows_the_dimensions_and_the_price_confirmation_form(): void
    {
        $order = $this->orderAwaitingPriceConfirmation();

        $this->actingAs($this->staff())->get(route('admin.orders.edit', $order))
            ->assertOk()
            ->assertSee('قطر خارجی')
            ->assertSee('تأیید قیمت نهایی');

        $this->actingAs($this->staff())->get(route('admin.orders.index'))->assertSee('نیاز به تأیید قیمت');
    }

    public function test_staff_confirm_the_final_price_and_every_total_follows(): void
    {
        $order = $this->orderAwaitingPriceConfirmation();
        $item = $order->items()->firstOrFail();

        $this->actingAs($this->staff())->put(route('admin.orders.update', $order), [
            'status' => 'confirmed', 'payment_status' => 'unpaid',
            'item_prices' => [$item->id => 130000], 'price_confirmed' => '1',
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(1_300_000, $item->refresh()->unit_price_rial);
        $this->assertSame(2_600_000, $item->line_total_rial);
        $this->assertSame(2_600_000, $order->subtotal_rial);
        $this->assertSame(3_100_000, $order->total_rial);
        $this->assertFalse($order->requires_price_confirmation);
        $this->assertSame([$item->id => 1_300_000], AuditLog::firstOrFail()->changes['item_prices_rial']);
    }

    public function test_prices_can_be_adjusted_without_confirming_yet(): void
    {
        $order = $this->orderAwaitingPriceConfirmation();
        $item = $order->items()->firstOrFail();

        $this->actingAs($this->staff())->put(route('admin.orders.update', $order), [
            'status' => 'pending', 'payment_status' => 'unpaid', 'item_prices' => [$item->id => 90000],
        ]);

        $this->assertSame(900_000, $item->refresh()->unit_price_rial);
        $this->assertTrue($order->refresh()->requires_price_confirmation);
    }

    public function test_prices_of_an_already_confirmed_order_cannot_be_changed(): void
    {
        $order = $this->orderAwaitingPriceConfirmation();
        $order->update(['requires_price_confirmation' => false]);
        $item = $order->items()->firstOrFail();

        $this->actingAs($this->staff())->put(route('admin.orders.update', $order), [
            'status' => 'confirmed', 'payment_status' => 'unpaid', 'item_prices' => [$item->id => 1],
        ]);

        $this->assertSame(1_000_000, $item->refresh()->unit_price_rial);
        $this->assertSame(2_500_000, $order->refresh()->total_rial);
    }
}
