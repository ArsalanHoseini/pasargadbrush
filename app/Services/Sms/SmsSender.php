<?php

namespace App\Services\Sms;

/**
 * The one seam every SMS in the app goes through. No real SMS provider has
 * been chosen/purchased yet (see docs/DECISIONS.md) — once it is, add one
 * implementation of this interface and bind it in AppServiceProvider via
 * the `services.sms.driver` config value. Nothing else needs to change.
 */
interface SmsSender
{
    /**
     * @param  string  $mobile  Iranian mobile number, e.g. 09121234567
     */
    public function send(string $mobile, string $message): void;
}
