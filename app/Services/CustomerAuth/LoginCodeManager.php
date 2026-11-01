<?php

namespace App\Services\CustomerAuth;

use App\Models\CustomerLoginCode;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Issues and checks one-time SMS login codes for customer accounts.
 *
 * Abuse limits: a new code at most once per RESEND_SECONDS per number and
 * MAX_CODES_PER_HOUR per number (SMS costs money and can be used to spam a
 * stranger's phone); each code expires after TTL_SECONDS and allows
 * MAX_ATTEMPTS guesses. Only a hash of the code is stored.
 */
class LoginCodeManager
{
    public const CODE_LENGTH = 5;

    public const TTL_SECONDS = 120;

    public const RESEND_SECONDS = 60;

    public const MAX_CODES_PER_HOUR = 5;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private SmsSender $sms) {}

    /**
     * @throws ValidationException when the number is rate limited or the SMS can't be sent.
     */
    public function send(string $phone, ?string $ipAddress = null): void
    {
        $latest = CustomerLoginCode::query()->where('phone', $phone)->latest('id')->first();

        if ($latest && $latest->created_at->diffInSeconds(now()) < self::RESEND_SECONDS) {
            $wait = self::RESEND_SECONDS - (int) $latest->created_at->diffInSeconds(now());

            throw ValidationException::withMessages(['phone' => "کد قبلی همین الان ارسال شد. لطفاً {$wait} ثانیه دیگر دوباره درخواست کنید."]);
        }

        $hourlyKey = 'customer-login-code:'.$phone;

        if (RateLimiter::tooManyAttempts($hourlyKey, self::MAX_CODES_PER_HOUR)) {
            throw ValidationException::withMessages(['phone' => 'تعداد درخواست کد برای این شماره زیاد بوده است. لطفاً کمی بعد دوباره تلاش کنید.']);
        }

        RateLimiter::hit($hourlyKey, 3600);

        // Only the newest code is ever valid.
        CustomerLoginCode::query()->where('phone', $phone)->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $code = (string) random_int(10 ** (self::CODE_LENGTH - 1), 10 ** self::CODE_LENGTH - 1);

        CustomerLoginCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds(self::TTL_SECONDS),
            'ip_address' => $ipAddress,
        ]);

        try {
            $this->sms->send($phone, setting('site_name', 'پاسارگاد برس').": کد ورود شما {$code}\nاین کد را در اختیار هیچ‌کس قرار ندهید.");
        } catch (\Throwable $e) {
            Log::warning('Failed to send customer login code', ['phone' => $phone, 'error' => $e->getMessage()]);

            throw ValidationException::withMessages(['phone' => 'ارسال پیامک ناموفق بود. لطفاً کمی بعد دوباره تلاش کنید یا با رمز عبور وارد شوید.']);
        }
    }

    /**
     * Consumes the code on success.
     *
     * @throws ValidationException when the code is wrong, expired or out of attempts.
     */
    public function verify(string $phone, string $code): void
    {
        $loginCode = CustomerLoginCode::query()
            ->where('phone', $phone)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $loginCode || $loginCode->expires_at->isPast()) {
            throw ValidationException::withMessages(['code' => 'کد منقضی شده است. لطفاً کد جدید درخواست کنید.']);
        }

        $loginCode->increment('attempts');

        if (! Hash::check($code, $loginCode->code_hash)) {
            if ($loginCode->attempts >= self::MAX_ATTEMPTS) {
                $loginCode->update(['consumed_at' => now()]);

                throw ValidationException::withMessages(['code' => 'تعداد تلاش‌های اشتباه زیاد بود. لطفاً کد جدید درخواست کنید.']);
            }

            throw ValidationException::withMessages(['code' => 'کد واردشده درست نیست.']);
        }

        $loginCode->update(['consumed_at' => now()]);
    }
}
