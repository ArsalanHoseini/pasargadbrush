<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The signed-in staff member's own name, email and password.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
        ], attributes: ['name' => 'نام', 'email' => 'ایمیل']);

        $user->fill($data);
        $changes = $user->getDirty();
        $user->save();

        if ($changes !== []) {
            AuditLog::record('profile.updated', $user, $changes);
        }

        return back()->with('status', 'مشخصات شما ذخیره شد.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], attributes: ['current_password' => 'رمز عبور فعلی', 'password' => 'رمز عبور جدید']);

        $request->user()->update(['password' => $data['password']]);
        AuditLog::record('profile.password_changed', $request->user());

        return back()->with('status', 'رمز عبور شما تغییر کرد.');
    }
}
