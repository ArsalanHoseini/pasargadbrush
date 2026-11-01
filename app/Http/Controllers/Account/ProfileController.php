<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', ['customer' => $request->user('customer')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $request->user('customer')->update($data);

        return redirect()->route('account.profile.edit')->with('status', 'اطلاعات شما ذخیره شد.');
    }

    /**
     * Setting or changing the password needs no current password: the
     * customer is already signed in (by SMS code or password), and the SMS
     * code login *is* the "forgot password" path.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], attributes: ['password' => 'رمز عبور']);

        $request->user('customer')->update(['password' => $data['password']]);

        return redirect()->route('account.profile.edit')->with('status', 'رمز عبور ذخیره شد. از این پس می‌توانید با شماره موبایل و رمز عبور هم وارد شوید.');
    }
}
