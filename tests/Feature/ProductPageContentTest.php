<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Customer;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductReview;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageContentTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);

        $this->product = Product::create([
            'sku' => 'S1', 'slug' => 'strip-brush', 'name' => 'برس استریپ', 'type' => 'quote', 'is_active' => true,
            'description' => '<p>توضیحات کامل برس استریپ</p>', 'specifications' => ['جنس پروفیل' => 'آلومینیوم'],
        ]);
    }

    private function media(string $path): Media
    {
        return Media::create(['disk' => 'public', 'path' => $path, 'mime_type' => 'image/png', 'size' => 1]);
    }

    public function test_key_features_and_applications_are_shown(): void
    {
        $this->product->highlights()->create(['title' => 'الیاف مقاوم و انعطاف‌پذیر', 'media_id' => $this->media('media/closeup.png')->id, 'sort_order' => 0]);
        $this->product->highlights()->create(['title' => 'پروفیل آلومینیومی با استحکام بالا', 'sort_order' => 1]);
        $door = Application::create(['name' => 'درب‌های صنعتی و اتوماتیک', 'icon_media_id' => $this->media('media/door.png')->id, 'sort_order' => 0]);
        $dust = Application::create(['name' => 'کنترل گردوغبار و ذرات', 'sort_order' => 1]);
        Application::create(['name' => 'کاربرد انتخاب‌نشده']);
        $this->product->applications()->attach([$door->id, $dust->id]);

        $this->get(route('products.show', $this->product))
            ->assertOk()
            ->assertSeeInOrder(['الیاف مقاوم و انعطاف‌پذیر', 'پروفیل آلومینیومی با استحکام بالا'])
            ->assertSee('media/closeup.png')
            ->assertSee('کاربردها')
            ->assertSeeInOrder(['درب‌های صنعتی و اتوماتیک', 'کنترل گردوغبار و ذرات'])
            ->assertSee('media/door.png')
            ->assertDontSee('کاربرد انتخاب‌نشده');
    }

    public function test_description_specifications_and_reviews_are_tabs(): void
    {
        $this->get(route('products.show', $this->product))
            ->assertSee('role="tablist"', false)
            ->assertSeeInOrder(['توضیحات', 'مشخصات فنی', 'نظرات مشتریان (0)'])
            ->assertSee('توضیحات کامل برس استریپ', false)
            ->assertSee('آلومینیوم')
            ->assertSee('هنوز نظری برای این محصول ثبت نشده است');
    }

    public function test_a_review_is_shown_only_after_approval(): void
    {
        $this->post(route('products.reviews.store', $this->product), [
            'author_name' => 'مهندس کریمی', 'rating' => 5, 'body' => 'کیفیت ساخت عالی بود.',
        ])->assertRedirect(route('products.show', $this->product).'#reviews')->assertSessionHas('review_sent');

        $review = ProductReview::firstOrFail();
        $this->assertSame('pending', $review->status);
        $this->get(route('products.show', $this->product))->assertDontSee('کیفیت ساخت عالی بود.')->assertSee('نظرات مشتریان (0)');

        $review->update(['status' => 'approved']);

        $this->get(route('products.show', $this->product))
            ->assertSee('کیفیت ساخت عالی بود.')
            ->assertSee('نظرات مشتریان (1)')
            ->assertSee('5.0 از ۵')
            ->assertDontSee('خریدار این محصول');
    }

    public function test_reviews_are_validated_and_bots_are_refused(): void
    {
        $this->post(route('products.reviews.store', $this->product), ['author_name' => '', 'body' => '', 'rating' => 9])
            ->assertSessionHasErrors(['author_name', 'body', 'rating']);

        $this->post(route('products.reviews.store', $this->product), [
            'author_name' => 'Bot', 'body' => 'spam', 'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, ProductReview::count());
    }

    public function test_a_logged_in_buyer_gets_a_verified_badge(): void
    {
        $customer = Customer::create(['name' => 'علی رضایی', 'phone' => '09121234567']);
        $order = $customer->orders()->create([
            'order_number' => 'PB-1', 'status' => 'completed', 'payment_status' => 'paid', 'subtotal_rial' => 1, 'total_rial' => 1, 'placed_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $this->product->id, 'name_snapshot' => 'برس استریپ', 'sku_snapshot' => 'S1',
            'unit_price_rial' => 1, 'quantity' => 1, 'line_total_rial' => 1,
        ]);

        $this->actingAs($customer, 'customer')->get(route('products.show', $this->product))
            ->assertSee('value="علی رضایی"', false);

        $this->actingAs($customer, 'customer')->post(route('products.reviews.store', $this->product), [
            'author_name' => 'علی رضایی', 'body' => 'بسیار راضی هستم.',
        ]);

        $review = ProductReview::firstOrFail();
        $this->assertSame($customer->id, $review->customer_id);
        $review->update(['status' => 'approved']);

        $this->get(route('products.show', $this->product))->assertSee('خریدار این محصول');
    }

    public function test_spam_reviews_and_reviews_of_inactive_products_stay_hidden(): void
    {
        $this->product->reviews()->create(['author_name' => 'x', 'body' => 'متن اسپم', 'status' => 'spam']);
        $this->get(route('products.show', $this->product))->assertDontSee('متن اسپم');

        $this->product->update(['is_active' => false]);
        $this->post(route('products.reviews.store', $this->product), ['author_name' => 'x', 'body' => 'y'])->assertNotFound();
    }
}
