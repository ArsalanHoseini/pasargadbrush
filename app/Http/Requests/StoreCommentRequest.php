<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['required', 'email', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            // Honeypot: a real visitor never fills this (hidden via CSS); a bot
            // filling every field usually does. Must stay empty.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'author_name.required' => 'لطفاً نام خود را وارد کنید.',
            'author_email.required' => 'لطفاً ایمیل خود را وارد کنید.',
            'body.required' => 'لطفاً متن دیدگاه را بنویسید.',
        ];
    }
}
