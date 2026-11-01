<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Checkout\ShippingCalculator;
use App\Services\Checkout\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Lets staff choose how shipping is charged. Amounts are entered in Toman
 * and stored in Rial, like product prices.
 */
class ShippingSettingController extends Controller
{
    private const GROUP = 'ارسال';

    public function edit(ShippingCalculator $shipping): View
    {
        return view('admin.shipping.edit', [
            'method' => $shipping->currentMethod(),
            'flatRateToman' => $this->toToman(setting(ShippingCalculator::FLAT_RATE_SETTING)),
            'freeThresholdToman' => $this->toToman(setting(ShippingCalculator::FREE_THRESHOLD_SETTING)),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shipping_method' => ['required', Rule::enum(ShippingMethod::class)],
            'flat_rate_toman' => ['nullable', 'required_if:shipping_method,'.ShippingMethod::Flat->value, 'integer', 'min:1'],
            'free_threshold_toman' => ['nullable', 'integer', 'min:1'],
        ], attributes: [
            'shipping_method' => 'روش محاسبه هزینه ارسال',
            'flat_rate_toman' => 'هزینه ارسال ثابت',
            'free_threshold_toman' => 'حداقل خرید برای ارسال رایگان',
        ]);

        $this->store(ShippingCalculator::METHOD_SETTING, $data['shipping_method']);
        $this->store(ShippingCalculator::FLAT_RATE_SETTING, $this->toRial($data['flat_rate_toman'] ?? null));
        $this->store(ShippingCalculator::FREE_THRESHOLD_SETTING, $this->toRial($data['free_threshold_toman'] ?? null));

        return redirect()->route('admin.shipping.edit')->with('status', 'تنظیمات ارسال ذخیره شد.');
    }

    private function store(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => self::GROUP]);
    }

    private function toRial(?int $toman): ?string
    {
        return $toman !== null ? (string) ($toman * 10) : null;
    }

    private function toToman(?string $rial): ?int
    {
        return $rial !== null && $rial !== '' ? intdiv((int) $rial, 10) : null;
    }
}
