<?php

namespace Tests\Feature\Admin;

use App\Models\Application;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->staff = User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(array $overrides = []): array
    {
        return array_merge(['name' => 'برس استریپ', 'sku' => 'S1', 'type' => 'quote'], $overrides);
    }

    public function test_guests_cannot_manage_applications_or_reviews(): void
    {
        $this->get(route('admin.applications.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.product-reviews.index'))->assertRedirect(route('admin.login'));
    }

    public function test_staff_create_edit_and_delete_an_application_with_an_icon(): void
    {
        $this->actingAs($this->staff)->post(route('admin.applications.store'), [
            'name' => 'شستشوی فرش', 'sort_order' => 2, 'icon' => UploadedFile::fake()->image('carpet.png', 64, 64),
        ])->assertRedirect(route('admin.applications.index'));

        $application = Application::with('icon')->firstOrFail();
        $this->assertSame('شستشوی فرش', $application->name);
        Storage::disk('public')->assertExists($application->icon->path);

        $this->actingAs($this->staff)->put(route('admin.applications.update', $application), ['name' => 'شستشوی فرش و موکت'])
            ->assertSessionHasNoErrors();
        $this->assertSame('شستشوی فرش و موکت', $application->refresh()->name);
        $this->assertNotNull($application->icon_media_id, 'Editing without a new icon keeps the old one.');

        $this->actingAs($this->staff)->get(route('admin.applications.index'))->assertSee('شستشوی فرش و موکت');

        $this->actingAs($this->staff)->delete(route('admin.applications.destroy', $application));
        $this->assertModelMissing($application);
    }

    public function test_an_svg_icon_is_refused(): void
    {
        $this->actingAs($this->staff)->post(route('admin.applications.store'), [
            'name' => 'x', 'icon' => UploadedFile::fake()->create('icon.svg', 1, 'image/svg+xml'),
        ])->assertSessionHasErrors('icon');
    }

    public function test_staff_set_key_features_and_applications_on_a_product(): void
    {
        $carpet = Application::create(['name' => 'شستشوی فرش']);
        $glass = Application::create(['name' => 'شستشوی شیشه']);

        $this->actingAs($this->staff)->post(route('admin.products.store'), $this->productPayload([
            'highlights' => [
                ['title' => 'الیاف مقاوم', 'image' => UploadedFile::fake()->image('closeup.png')],
                ['title' => 'هسته مقاوم'],
            ],
            'application_ids' => [$carpet->id],
        ]))->assertSessionHasNoErrors();

        $product = Product::with('highlights.image', 'applications')->firstOrFail();
        $this->assertSame(['الیاف مقاوم', 'هسته مقاوم'], $product->highlights->pluck('title')->all());
        $this->assertNotNull($product->highlights[0]->image);
        $this->assertNull($product->highlights[1]->media_id);
        $this->assertSame([$carpet->id], $product->applications->pluck('id')->all());

        [$first, $second] = $product->highlights->all();

        $this->actingAs($this->staff)->put(route('admin.products.update', $product), $this->productPayload([
            'highlights' => [
                ['id' => $second->id, 'title' => 'هسته مقاوم (پلاستیک/فلز)'],
                ['id' => $first->id, 'title' => 'الیاف مقاوم', 'remove_image' => '1'],
            ],
            'application_ids' => [$glass->id],
        ]))->assertSessionHasNoErrors();

        $product->refresh()->load('highlights', 'applications');
        $this->assertSame(['هسته مقاوم (پلاستیک/فلز)', 'الیاف مقاوم'], $product->highlights->pluck('title')->all());
        $this->assertNull($product->highlights[1]->media_id);
        $this->assertSame([$glass->id], $product->applications->pluck('id')->all());

        $this->actingAs($this->staff)->put(route('admin.products.update', $product), $this->productPayload());
        $this->assertSame(0, $product->highlights()->count());
        $this->assertSame(0, $product->applications()->count());
    }

    public function test_at_most_four_key_features(): void
    {
        $this->actingAs($this->staff)->post(route('admin.products.store'), $this->productPayload([
            'highlights' => array_map(fn ($i) => ['title' => "ویژگی {$i}"], range(1, 5)),
        ]))->assertSessionHasErrors('highlights');
    }

    public function test_staff_moderate_reviews_with_a_pending_badge(): void
    {
        $product = Product::create(['sku' => 'S1', 'slug' => 's1', 'name' => 'برس', 'type' => 'quote', 'is_active' => true]);
        $review = $product->reviews()->create(['author_name' => 'مشتری', 'rating' => 4, 'body' => 'خوب بود', 'status' => 'pending']);

        $this->actingAs($this->staff)->get(route('admin.dashboard'))->assertSee('نظرات محصولات');
        $this->actingAs($this->staff)->get(route('admin.product-reviews.index'))
            ->assertOk()
            ->assertSee('خوب بود')
            ->assertSee('★★★★☆');

        $this->actingAs($this->staff)->post(route('admin.product-reviews.approve', $review));
        $this->assertSame('approved', $review->refresh()->status);

        $this->actingAs($this->staff)->post(route('admin.product-reviews.spam', $review));
        $this->assertSame('spam', $review->refresh()->status);

        $this->actingAs($this->staff)->delete(route('admin.product-reviews.destroy', $review));
        $this->assertSame(0, ProductReview::count());
    }
}
