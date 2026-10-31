<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Placeholder until a real SMS provider is chosen: writes the message to the
 * application log instead of sending it, so the whole notification flow can
 * already be exercised end to end.
 */
class LogSmsSender implements SmsSender
{
    public function send(string $mobile, string $message): void
    {
        Log::info('SMS (log driver — not actually sent)', [
            'mobile' => $mobile,
            'message' => $message,
        ]);
    }
}
