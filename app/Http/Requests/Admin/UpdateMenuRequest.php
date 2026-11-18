<?php

namespace App\Http\Requests\Admin;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuRequest extends FormRequest
{
    /**
     * Site-relative paths, full http(s) addresses, phone and e-mail links.
     */
    private const URL_PATTERN = '/^(\/|https?:\/\/|tel:|mailto:|#)/i';

    public function authorize(): bool
    {
        return true; // gated by the admin.staff route middleware
    }

    /**
     * The editor posts the whole tree as one JSON field.
     */
    protected function prepareForValidation(): void
    {
        $decoded = json_decode((string) $this->input('menu_json', '[]'), true);

        $this->merge(['menu' => is_array($decoded) ? $decoded : null]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'menu' => ['present', 'array', 'max:30'],
            'menu.*.label' => ['required', 'string', 'max:100'],
            'menu.*.type' => ['required', Rule::in([MenuItem::TYPE_LINK, MenuItem::TYPE_CATEGORIES])],
            'menu.*.url' => ['nullable', 'required_if:menu.*.type,'.MenuItem::TYPE_LINK, 'string', 'max:2048', 'regex:'.self::URL_PATTERN],
            'menu.*.open_in_new_tab' => ['boolean'],
            'menu.*.children' => ['array', 'max:30'],
            'menu.*.children.*.label' => ['required', 'string', 'max:100'],
            'menu.*.children.*.url' => ['required', 'string', 'max:2048', 'regex:'.self::URL_PATTERN],
            'menu.*.children.*.open_in_new_tab' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'menu.array' => 'اطلاعات منو نامعتبر است؛ صفحه را دوباره بارگذاری کنید.',
            'menu.*.label.required' => 'عنوان همه‌ی آیتم‌های منو را وارد کنید.',
            'menu.*.children.*.label.required' => 'عنوان همه‌ی زیرمنوها را وارد کنید.',
            'menu.*.url.required_if' => 'برای هر پیوند، آدرس را وارد کنید.',
            'menu.*.children.*.url.required' => 'برای هر زیرمنو، آدرس را وارد کنید.',
            'menu.*.url.regex' => 'آدرس باید با «/» (صفحه‌ای از همین سایت) یا http:// ،https:// ،tel: یا mailto: شروع شود.',
            'menu.*.children.*.url.regex' => 'آدرس باید با «/» (صفحه‌ای از همین سایت) یا http:// ،https:// ،tel: یا mailto: شروع شود.',
        ];
    }
}
