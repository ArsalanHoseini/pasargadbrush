<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerLoginCode;
use App\Models\User;
use App\Services\Sms\SmsSender;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CustomerLoginTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{mobile: string, message: string}> */
    private array $sms = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);

        $this->app->instance(SmsSender::class, new class($this->sms) implements SmsSender
        {
            public function __construct(private array &$sent) {}

            public function send(string $mobile, string $message): void
            {
                $this->sent[] = ['mobile' => $mobile, 'message' => $message];
            }
        });
    }

    private function requestCode(string $phone = '09121234567'): TestResponse
    {
        return $this->post(route('account.login.code'), ['phone' => $phone]);
    }

    private function lastCode(): string
    {
        preg_match('/\d{5}/', end($this->sms)['message'], $match);

        return $match[0];
    }

    public function test_login_page_is_linked_from_the_header(): void
    {
        $this->get(route('home'))->assertSee(route('account.login'));
        $this->get(route('account.login'))->assertOk()->assertSee('ورود با کد پیامکی')->assertSee('ورود با رمز عبور');
    }

    public function test_requesting_a_code_texts_a_five_digit_code_and_stores_only_its_hash(): void
    {
        $this->requestCode('۰۹۱۲ ۱۲۳ ۴۵۶۷')->assertRedirect(route('account.login.verify'));

        $this->assertSame('09121234567', $this->sms[0]['mobile']);
        $code = $this->lastCode();
        $this->assertNotSame($code, CustomerLoginCode::firstOrFail()->code_hash);
        $this->get(route('account.login.verify'))->assertSee('09121234567');
    }

    public function test_an_invalid_mobile_gets_no_code(): void
    {
        $this->requestCode('07133334444')->assertSessionHasErrors('phone');
        $this->assertSame([], $this->sms);
    }

    public function test_a_new_code_cannot_be_requested_within_a_minute_or_more_than_five_times_an_hour(): void
    {
        $this->requestCode();
        $this->requestCode()->assertSessionHasErrors('phone');
        $this->assertCount(1, $this->sms);

        foreach (range(1, 4) as $ignored) {
            $this->travel(61)->seconds();
            $this->requestCode()->assertSessionHasNoErrors();
        }

        $this->travel(61)->seconds();
        $this->requestCode()->assertSessionHasErrors('phone');
        $this->assertCount(5, $this->sms);
    }

    public function test_the_right_code_creates_a_verified_account_and_asks_for_a_name(): void
    {
        $this->requestCode();

        $this->post(route('account.login.verify.store'), ['code' => $this->lastCode()])
            ->assertRedirect(route('account.profile.edit'));

        $customer = Customer::firstOrFail();
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertNotNull($customer->phone_verified_at);
        $this->assertSame('09121234567', $customer->phone);
    }

    public function test_an_existing_customer_lands_on_their_dashboard_with_their_guest_orders(): void
    {
        $customer = Customer::create(['name' => 'علی', 'phone' => '09121234567']);
        $customer->orders()->create([
            'order_number' => 'PB-GUEST-1', 'status' => 'pending', 'payment_status' => 'unpaid',
            'subtotal_rial' => 10, 'total_rial' => 10, 'placed_at' => now(),
        ]);
        $this->requestCode();

        $this->post(route('account.login.verify.store'), ['code' => $this->lastCode()])
            ->assertRedirect(route('account.dashboard'));

        $this->assertSame(1, Customer::count());
        $this->get(route('account.orders.index'))->assertSee('PB-GUEST-1');
    }

    public function test_a_code_typed_with_persian_digits_works(): void
    {
        $this->requestCode();
        $persian = strtr($this->lastCode(), ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);

        $this->post(route('account.login.verify.store'), ['code' => $persian])->assertSessionHasNoErrors();
        $this->assertAuthenticated('customer');
    }

    public function test_a_wrong_code_fails_and_five_wrong_tries_burn_the_code(): void
    {
        $this->requestCode();
        $right = $this->lastCode();
        $wrong = $right === '11111' ? '22222' : '11111';

        foreach (range(1, 5) as $ignored) {
            $this->post(route('account.login.verify.store'), ['code' => $wrong])->assertSessionHasErrors('code');
        }

        $this->post(route('account.login.verify.store'), ['code' => $right])
            ->assertSessionHasErrors(['code' => 'کد منقضی شده است. لطفاً کد جدید درخواست کنید.']);
        $this->assertGuest('customer');
    }

    public function test_an_expired_code_is_refused(): void
    {
        $this->requestCode();
        $this->travel(3)->minutes();

        $this->post(route('account.login.verify.store'), ['code' => $this->lastCode()])->assertSessionHasErrors('code');
        $this->assertGuest('customer');
    }

    public function test_only_the_newest_code_is_valid(): void
    {
        $this->requestCode();
        $first = $this->lastCode();
        $this->travel(61)->seconds();
        $this->requestCode();

        if ($first !== $this->lastCode()) {
            $this->post(route('account.login.verify.store'), ['code' => $first])->assertSessionHasErrors('code');
        }

        $this->post(route('account.login.verify.store'), ['code' => $this->lastCode()])->assertSessionHasNoErrors();
    }

    public function test_password_login_works_once_a_password_is_set(): void
    {
        Customer::create(['name' => 'علی', 'phone' => '09121234567', 'password' => 'secret-pass']);

        $this->post(route('account.login.password'), ['phone' => '09121234567', 'password' => 'wrong-pass'])
            ->assertSessionHasErrors('phone');
        $this->assertGuest('customer');

        $this->post(route('account.login.password'), ['phone' => '+98 912 123 4567', 'password' => 'secret-pass'])
            ->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticated('customer');
    }

    public function test_an_account_without_a_password_cannot_log_in_with_one(): void
    {
        Customer::create(['name' => 'علی', 'phone' => '09121234567']);

        $this->post(route('account.login.password'), ['phone' => '09121234567', 'password' => ''])
            ->assertSessionHasErrors('password');
        $this->post(route('account.login.password'), ['phone' => '09121234567', 'password' => 'anything'])
            ->assertSessionHasErrors('phone');
    }

    public function test_dashboard_requires_login_and_redirects_to_the_customer_login(): void
    {
        $this->get(route('account.dashboard'))->assertRedirect(route('account.login'));
    }

    public function test_customer_logout_does_not_sign_out_staff_in_the_same_browser(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['name' => 'علی', 'phone' => '09121234567']);

        $this->actingAs($staff)->actingAs($customer, 'customer')
            ->post(route('account.logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest('customer');
        $this->assertAuthenticatedAs($staff, 'web');
    }
}
