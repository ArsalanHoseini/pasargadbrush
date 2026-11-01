<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerAuth\LoginCodeManager;
use App\Support\IranianMobile;
use App\Support\PersianDigits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Customer login by mobile number: either a one-time SMS code (which also
 * creates the account on first login and verifies the number) or, once the
 * customer has set one in their dashboard, a password.
 *
 * Customers are matched by phone exactly like checkout, so after the first
 * SMS login every earlier guest order placed with that number is already in
 * the account.
 */
class LoginController extends Controller
{
    private const SESSION_PHONE = 'customer_login_phone';

    public function create(): View
    {
        return view('account.auth.login');
    }

    public function sendCode(Request $request, LoginCodeManager $codes): RedirectResponse
    {
        $phone = $this->validatedPhone($request);

        $codes->send($phone, $request->ip());

        $request->session()->put(self::SESSION_PHONE, $phone);

        return redirect()->route('account.login.verify')->with('status', 'کد ورود به شماره‌ی شما پیامک شد.');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_PHONE);

        if (! $phone) {
            return redirect()->route('account.login');
        }

        return view('account.auth.verify', [
            'phone' => $phone,
            'resendAfterSeconds' => LoginCodeManager::RESEND_SECONDS,
        ]);
    }

    public function verify(Request $request, LoginCodeManager $codes): RedirectResponse
    {
        $phone = $request->session()->get(self::SESSION_PHONE);

        if (! $phone) {
            return redirect()->route('account.login');
        }

        $data = $request->validate(['code' => ['required', 'string', 'max:10']], attributes: ['code' => 'کد ورود']);

        $codes->verify($phone, preg_replace('/\D/', '', PersianDigits::toLatin($data['code'])) ?? '');

        $customer = Customer::query()->firstOrCreate(['phone' => $phone], ['name' => '']);
        $customer->forceFill(['phone_verified_at' => $customer->phone_verified_at ?? now()])->save();

        $request->session()->forget(self::SESSION_PHONE);

        return $this->logIn($request, $customer);
    }

    public function passwordLogin(Request $request): RedirectResponse
    {
        $phone = $this->validatedPhone($request);
        $password = (string) $request->validate(['password' => ['required', 'string']])['password'];

        $throttleKey = 'customer-password-login:'.$phone.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'phone' => 'تلاش‌های ناموفق زیاد بود. لطفاً '.RateLimiter::availableIn($throttleKey).' ثانیه دیگر دوباره تلاش کنید.',
            ]);
        }

        $customer = Customer::query()->where('phone', $phone)->first();

        if (! $customer?->hasPassword() || ! Hash::check($password, $customer->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'phone' => 'شماره موبایل یا رمز عبور درست نیست. اگر رمز عبور تعریف نکرده‌اید، با کد پیامکی وارد شوید.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        return $this->logIn($request, $customer);
    }

    public function destroy(Request $request): RedirectResponse
    {
        // Only the customer guard: staff signed into the admin in the same
        // browser stay signed in.
        Auth::guard('customer')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function logIn(Request $request, Customer $customer): RedirectResponse
    {
        Auth::guard('customer')->login($customer, remember: true);
        $request->session()->regenerate();
        $customer->forceFill(['last_login_at' => now()])->save();

        if ($customer->name === '') {
            return redirect()->route('account.profile.edit')
                ->with('status', 'خوش آمدید! لطفاً نام خود را کامل کنید.');
        }

        return redirect()->intended(route('account.dashboard'));
    }

    private function validatedPhone(Request $request): string
    {
        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => IranianMobile::normalize($request->input('phone'))]);
        }

        return $request->validate(
            ['phone' => ['required', 'string', 'regex:'.IranianMobile::PATTERN]],
            ['phone.regex' => 'شماره موبایل معتبر نیست — مثال: ۰۹۱۲۱۲۳۴۵۶۷'],
            ['phone' => 'شماره موبایل'],
        )['phone'];
    }
}
