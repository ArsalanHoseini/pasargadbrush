<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Media;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDimensionField;
use App\Models\ProductVariant;
use App\Models\QuoteRequest;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductCustomizationTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private ProductVariant $variant;

    private ProductDimensionField $outerDiameter;

    private ProductDimensionField $length;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Mail::fake();

        $schematic = Media::create(['disk' => 'public', 'path' => 'media/schematic.png', 'mime_type' => 'image/png', 'size' => 100]);

        $this->product = Product::create([
            'sku' => 'P1', 'slug' => 'roller-brush', 'name' => 'برس غلتکی', 'type' => 'priced',
            'is_active' => true, 'schematic_media_id' => $schematic->id,
        ]);
        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id, 'sku' => 'V1', 'label' => 'موی نایلونی', 'price_rial' => 1_000_000,
        ]);
        $this->outerDiameter = ProductDimensionField::factory()->create([
            'product_id' => $this->product->id, 'code' => 'D', 'label' => 'قطر خارجی', 'unit' => 'mm',
            'min_value' => 50, 'max_value' => 400, 'sort_order' => 0,
        ]);
        $this->length = ProductDimensionField::factory()->create([
            'product_id' => $this->product->id, 'code' => 'C', 'label' => 'طول', 'unit' => 'cm', 'sort_order' => 1,
        ]);
    }

    /**
     * @param  array<int, string>  $dimensions  Keyed by field id.
     */
    private function addCustomized(array $dimensions, int $quantity = 1, ?string $token = null, ?string $notes = null): TestResponse
    {
        $request = $token ? $this->withUnencryptedCookie('cart_token', $token) : $this;

        return $request
            ->from(route('products.customize', ['product' => $this->product, 'variant' => $this->variant->id]))
            ->post(route('cart.items.store'), [
                'product_variant_id' => $this->variant->id,
                'quantity' => $quantity,
                'dimensions' => $dimensions,
                'customization_notes' => $notes,
            ]);
    }

    private function tokenFrom(TestResponse $response): string
    {
        return collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'cart_token')->getValue();
    }

    public function test_product_page_sends_add_to_cart_through_the_dimensions_step(): void
    {
        $this->get(route('products.show', $this->product))
            ->assertOk()
            ->assertSee(route('products.customize', $this->product), false)
            ->assertSee('فرم ابعاد نمایش داده می‌شود');
    }

    public function test_dimensions_page_shows_the_schematic_and_every_field_with_its_letter_and_unit(): void
    {
        $this->get(route('products.customize', ['product' => $this->product, 'variant' => $this->variant->id, 'quantity' => 3]))
            ->assertOk()
            ->assertSee('media/schematic.png')
            ->assertSeeInOrder(['D', 'قطر خارجی', 'میلی‌متر', 'بین 50 تا 400', 'C', 'طول', 'سانتی‌متر'])
            ->assertSee('value="3"', false)
            ->assertSee('قیمت نهایی پس از بررسی ابعاد توسط کارشناس');
    }

    public function test_dimensions_page_redirects_for_a_variant_of_another_product(): void
    {
        $this->get(route('products.customize', ['product' => $this->product, 'variant' => 999]))
            ->assertRedirect(route('products.show', $this->product));
    }

    public function test_a_product_without_dimensions_skips_the_dimensions_step(): void
    {
        $this->product->dimensionFields()->delete();

        $this->get(route('products.customize', ['product' => $this->product, 'variant' => $this->variant->id]))
            ->assertRedirect(route('products.show', $this->product));

        $this->post(route('cart.items.store'), ['product_variant_id' => $this->variant->id, 'quantity' => 1])
            ->assertSessionHasNoErrors();
        $this->assertNull(CartItem::firstOrFail()->customization);
    }

    public function test_cannot_add_without_the_required_dimensions(): void
    {
        $this->post(route('cart.items.store'), ['product_variant_id' => $this->variant->id, 'quantity' => 1])
            ->assertSessionHasErrors('dimensions');

        $this->addCustomized([$this->outerDiameter->id => '120'])
            ->assertSessionHasErrors(['dimensions.'.$this->length->id => 'وارد کردن طول (C) الزامی است.']);

        $this->assertSame(0, CartItem::count());
    }

    public function test_dimension_limits_are_enforced(): void
    {
        $this->addCustomized([$this->outerDiameter->id => '500', $this->length->id => '30'])
            ->assertSessionHasErrors(['dimensions.'.$this->outerDiameter->id => 'قطر خارجی (D) نباید بیشتر از 400 باشد.']);

        $this->addCustomized([$this->outerDiameter->id => '0', $this->length->id => '30'])
            ->assertSessionHasErrors('dimensions.'.$this->outerDiameter->id);
    }

    public function test_dimensions_typed_with_persian_digits_are_stored_as_a_snapshot_on_the_cart_line(): void
    {
        $this->addCustomized([$this->outerDiameter->id => '۱۲۰٫۵', $this->length->id => '۳۰'], notes: 'موی سیمی')
            ->assertRedirect(route('cart.show'));

        $this->assertSame([
            'dimensions' => [
                ['code' => 'D', 'label' => 'قطر خارجی', 'unit' => 'mm', 'value' => '120.5'],
                ['code' => 'C', 'label' => 'طول', 'unit' => 'cm', 'value' => '30'],
            ],
            'notes' => 'موی سیمی',
        ], CartItem::firstOrFail()->customization);
    }

    public function test_the_same_variant_in_two_sizes_is_two_cart_lines(): void
    {
        $token = $this->tokenFrom($this->addCustomized([$this->outerDiameter->id => '120', $this->length->id => '30']));
        $this->addCustomized([$this->outerDiameter->id => '200', $this->length->id => '30'], token: $token);

        $this->assertSame(2, CartItem::count());

        $this->withUnencryptedCookie('cart_token', $token)->get(route('cart.show'))
            ->assertSee('120')
            ->assertSee('200')
            ->assertSee('قیمت نهایی پس از بررسی ابعاد توسط کارشناس');
    }

    public function test_stock_is_checked_across_all_lines_of_the_variant(): void
    {
        $this->variant->update(['stock_quantity' => 5]);
        $token = $this->tokenFrom($this->addCustomized([$this->outerDiameter->id => '120', $this->length->id => '30'], quantity: 4));

        $this->addCustomized([$this->outerDiameter->id => '200', $this->length->id => '30'], quantity: 2, token: $token)
            ->assertSessionHasErrors(['quantity' => 'از این محصول فقط 5 عدد موجود است.']);

        $this->assertSame(1, CartItem::count());
    }

    public function test_the_order_keeps_the_dimensions_and_waits_for_price_confirmation(): void
    {
        $token = $this->tokenFrom($this->addCustomized([$this->outerDiameter->id => '120', $this->length->id => '30'], quantity: 2));

        $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [
            'name' => 'علی', 'phone' => '09120000000',
            'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس',
        ])->assertRedirect(route('checkout.confirmation'));

        $order = Order::with('items')->firstOrFail();
        $this->assertTrue($order->requires_price_confirmation);
        $this->assertSame('120', $order->items->first()->customization['dimensions'][0]['value']);
        $this->assertSame(2_000_000, $order->subtotal_rial);

        $this->get(route('checkout.confirmation'))
            ->assertSee('قطر خارجی')
            ->assertSee('قیمت نهایی پس از بررسی ابعاد توسط کارشناس');
    }

    public function test_editing_the_product_form_later_does_not_change_a_placed_line(): void
    {
        $this->addCustomized([$this->outerDiameter->id => '120', $this->length->id => '30']);

        $this->outerDiameter->update(['label' => 'عنوان جدید', 'unit' => 'cm']);

        $this->assertSame('قطر خارجی', CartItem::firstOrFail()->customization['dimensions'][0]['label']);
    }

    public function test_an_order_without_custom_lines_needs_no_price_confirmation(): void
    {
        $this->product->dimensionFields()->delete();
        $token = $this->tokenFrom($this->post(route('cart.items.store'), ['product_variant_id' => $this->variant->id, 'quantity' => 1]));

        $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [
            'name' => 'علی', 'phone' => '09120000000',
            'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس',
        ]);

        $this->assertFalse(Order::firstOrFail()->requires_price_confirmation);
    }

    public function test_quote_request_for_a_product_with_dimensions_requires_and_stores_them(): void
    {
        $this->product->update(['type' => 'quote']);

        $this->get(route('products.show', $this->product))
            ->assertSee('ابعاد مورد نیاز')
            ->assertSee('name="dimensions['.$this->outerDiameter->id.']"', false);

        $this->post(route('quote-requests.store'), [
            'product_id' => $this->product->id, 'name' => 'علی', 'phone' => '09120000000', 'message' => 'برای خط تولید',
        ])->assertSessionHasErrors('dimensions');

        $this->post(route('quote-requests.store'), [
            'product_id' => $this->product->id, 'name' => 'علی', 'phone' => '09120000000', 'message' => 'برای خط تولید',
            'dimensions' => [$this->outerDiameter->id => '150', $this->length->id => '40'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('150', QuoteRequest::firstOrFail()->customization['dimensions'][0]['value']);
    }

    public function test_a_general_quote_request_is_unaffected(): void
    {
        $this->post(route('quote-requests.store'), [
            'name' => 'علی', 'phone' => '09120000000', 'message' => 'درخواست عمومی',
        ])->assertSessionHasNoErrors();

        $this->assertNull(QuoteRequest::firstOrFail()->customization);
    }
}
