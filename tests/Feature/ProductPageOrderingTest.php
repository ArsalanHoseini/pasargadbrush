<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Media;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductDimensionField;
use App\Models\ProductVariant;
use App\Models\QuoteRequest;
use App\Models\User;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProductPageOrderingTest extends TestCase
{
    use RefreshDatabase;

    private Product $quoteProduct;

    private ProductDimensionField $diameter;

    private ProductDimensionField $fiber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        Mail::fake();

        $this->quoteProduct = Product::create([
            'sku' => 'Q1', 'slug' => 'cylinder-brush', 'name' => 'برس سیلندری', 'type' => 'quote', 'is_active' => true,
            'short_description' => 'برس صنعتی استوانه‌ای مناسب شستشو.',
        ]);
        $this->diameter = ProductDimensionField::factory()->create([
            'product_id' => $this->quoteProduct->id, 'code' => 'D', 'label' => 'قطر خارجی برس', 'sort_order' => 0,
        ]);
        $this->fiber = ProductDimensionField::factory()->create([
            'product_id' => $this->quoteProduct->id, 'type' => 'choice', 'code' => '', 'label' => 'جنس الیاف',
            'options' => ['نایلون (PA)', 'PET', 'PP', 'سایر'], 'sort_order' => 1,
        ]);
    }

    private function addQuoteProduct(array $overrides = []): TestResponse
    {
        return $this->from(route('products.show', $this->quoteProduct))->post(route('cart.items.store'), array_merge([
            'product_id' => $this->quoteProduct->id,
            'quantity' => 4,
            'dimensions' => [$this->diameter->id => '۲۵۰', $this->fiber->id => 'PET'],
        ], $overrides));
    }

    private function tokenFrom(TestResponse $response): string
    {
        return collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === 'cart_token')->getValue();
    }

    public function test_quote_only_product_page_shows_the_order_card_with_choice_chips_and_schematics(): void
    {
        foreach (['front.png', 'section.png'] as $position => $path) {
            $media = Media::create(['disk' => 'public', 'path' => 'media/'.$path, 'mime_type' => 'image/png', 'size' => 1]);
            $this->quoteProduct->schematics()->attach($media->id, ['role' => 'schematic', 'sort_order' => $position]);
        }

        $this->get(route('products.show', $this->quoteProduct))
            ->assertOk()
            ->assertSee('برس صنعتی استوانه‌ای مناسب شستشو.')
            ->assertSeeInOrder(['قطر خارجی برس', 'جنس الیاف', 'نایلون (PA)', 'PET', 'PP', 'سایر'])
            ->assertSee('type="radio" name="dimensions['.$this->fiber->id.']" value="PET"', false)
            ->assertSee('قیمت پس از بررسی کارشناس اعلام می‌شود')
            ->assertSee('افزودن به سبد و ثبت سفارش')
            ->assertSeeInOrder(['media/front.png', 'media/section.png']);
    }

    public function test_a_quote_only_product_goes_into_the_cart_without_a_price(): void
    {
        $this->addQuoteProduct()->assertRedirect(route('cart.show'))->assertSessionHasNoErrors();

        $item = CartItem::firstOrFail();
        $this->assertSame($this->quoteProduct->id, $item->product_id);
        $this->assertNull($item->product_variant_id);
        $this->assertSame(['250', 'PET'], array_column($item->customization['dimensions'], 'value'));

        $this->withUnencryptedCookie('cart_token', $this->tokenFrom($this->addQuoteProduct()))
            ->get(route('cart.show'))
            ->assertSee('قیمت پس از بررسی کارشناس')
            ->assertSee('جنس الیاف')
            ->assertSee('پس از بررسی کارشناس تأیید');
    }

    public function test_a_choice_must_be_one_of_its_options(): void
    {
        $this->addQuoteProduct(['dimensions' => [$this->diameter->id => '250', $this->fiber->id => 'چوب']])
            ->assertSessionHasErrors('dimensions.'.$this->fiber->id);

        $this->addQuoteProduct(['dimensions' => [$this->diameter->id => '250']])
            ->assertSessionHasErrors('dimensions.'.$this->fiber->id);

        $this->assertSame(0, CartItem::count());
    }

    public function test_an_inactive_or_out_of_stock_quote_product_cannot_be_ordered(): void
    {
        $this->quoteProduct->update(['stock_status' => 'out_of_stock']);
        $this->addQuoteProduct()->assertSessionHasErrors(['quantity' => 'این محصول در حال حاضر ناموجود است.']);

        $this->quoteProduct->update(['stock_status' => null, 'is_active' => false]);
        $this->addQuoteProduct()->assertNotFound();

        $this->assertSame(0, CartItem::count());
    }

    public function test_a_priced_product_needs_a_variant_and_a_quote_product_refuses_one(): void
    {
        $priced = Product::create(['sku' => 'P1', 'slug' => 'p1', 'name' => 'برس', 'type' => 'priced', 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $priced->id, 'sku' => 'V1', 'label' => 'بزرگ', 'price_rial' => 1000]);

        $this->post(route('cart.items.store'), ['product_id' => $priced->id, 'quantity' => 1])
            ->assertSessionHasErrors('product_variant_id');

        $this->post(route('cart.items.store'), ['product_id' => $this->quoteProduct->id, 'product_variant_id' => $variant->id, 'quantity' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame($priced->id, CartItem::firstOrFail()->product_id, 'The variant decides the product, not a mismatched product_id.');
    }

    public function test_ordering_a_quote_product_flags_the_order_and_staff_set_the_price(): void
    {
        $token = $this->tokenFrom($this->addQuoteProduct());

        $this->withUnencryptedCookie('cart_token', $token)->post(route('checkout.store'), [
            'name' => 'علی', 'phone' => '09120000000', 'province' => 'فارس', 'city' => 'مرودشت', 'address_line' => 'آدرس',
        ])->assertRedirect(route('checkout.confirmation'));

        $order = Order::with('items')->firstOrFail();
        $item = $order->items->first();
        $this->assertTrue($order->requires_price_confirmation);
        $this->assertSame(0, $item->unit_price_rial);
        $this->assertSame('برس سیلندری', $item->name_snapshot);
        $this->assertTrue($item->isAwaitingPrice($order));

        $this->get(route('checkout.confirmation'))->assertSee('در انتظار تعیین قیمت');

        $staff = User::factory()->create(['role' => 'admin']);
        $this->actingAs($staff)->put(route('admin.orders.update', $order), [
            'status' => 'confirmed', 'payment_status' => 'unpaid', 'item_prices' => [$item->id => 3_000_000], 'price_confirmed' => '1',
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(120_000_000, $order->subtotal_rial);
        $this->assertFalse($item->refresh()->isAwaitingPrice($order));
    }

    public function test_the_consult_button_sends_the_card_as_a_quote_request(): void
    {
        $this->from(route('products.show', $this->quoteProduct))->post(route('quote-requests.store'), [
            'product_id' => $this->quoteProduct->id,
            'quantity' => 12,
            'dimensions' => [$this->diameter->id => '300', $this->fiber->id => 'PP'],
            'order_mode' => 'quote',
            'name' => 'علی', 'phone' => '09120000000', 'message' => 'برای خط شستشوی میوه',
        ])->assertRedirect(route('products.show', $this->quoteProduct))->assertSessionHasNoErrors();

        $quoteRequest = QuoteRequest::firstOrFail();
        $this->assertSame('12', $quoteRequest->requested_quantity);
        $this->assertSame(['300', 'PP'], array_column($quoteRequest->customization['dimensions'], 'value'));
        $this->assertSame(0, CartItem::count());

        $this->followingRedirects()->from(route('products.show', $this->quoteProduct))->post(route('quote-requests.store'), [
            'product_id' => $this->quoteProduct->id, 'order_mode' => 'quote',
            'dimensions' => [$this->diameter->id => '300', $this->fiber->id => 'PP'],
            'name' => 'علی', 'phone' => '09120000000', 'message' => 'دوباره',
        ])->assertSee('درخواست شما ثبت شد');
    }
}
