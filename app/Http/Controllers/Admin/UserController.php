<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Staff accounts (admins only). An admin can never remove or demote
 * themself, and the last admin always stays, so nobody gets locked out.
 */
class UserController extends Controller
{
    public function index(): View
    {
        $lastActivity = AuditLog::query()
            ->selectRaw('user_id, max(created_at) as last_at')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');

        return view('admin.users.index', [
            'users' => User::query()->orderByRaw("role = 'admin' desc")->orderBy('name')->get(),
            'lastActivity' => $lastActivity,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => 'editor'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, new User);

        $user = User::create($data);
        AuditLog::record('user.created', $user, ['name' => $user->name, 'email' => $user->email, 'role' => $user->role]);

        return redirect()->route('admin.users.index')->with('status', 'حساب «'.$user->name.'» ساخته شد.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if (($data['role'] ?? $user->role) !== 'admin' && $user->isAdmin()) {
            if ($user->is($request->user())) {
                return back()->withInput()->withErrors(['role' => 'نمی‌توانید نقش مدیر را از حساب خودتان بردارید.']);
            }

            if ($this->isLastAdmin($user)) {
                return back()->withInput()->withErrors(['role' => 'این تنها مدیر سایت است؛ ابتدا مدیر دیگری تعریف کنید.']);
            }
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->fill($data);
        $changes = collect($user->getDirty())->except(['password', 'remember_token'])->all();

        if ($user->isDirty('password')) {
            $changes['password_changed'] = true;
        }

        $user->save();

        if ($changes !== []) {
            AuditLog::record('user.updated', $user, $changes);
        }

        return redirect()->route('admin.users.index')->with('status', 'حساب «'.$user->name.'» به‌روزرسانی شد.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'نمی‌توانید حساب خودتان را حذف کنید.');
        }

        if ($user->isAdmin() && $this->isLastAdmin($user)) {
            return back()->with('error', 'این تنها مدیر سایت است و حذف نمی‌شود.');
        }

        AuditLog::record('user.deleted', $user, ['name' => $user->name, 'email' => $user->email]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'حساب «'.$user->name.'» حذف شد.');
    }

    /**
     * @return array{name: string, email: string, role: string, password?: string|null}
     */
    private function validated(Request $request, User $user): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLE_LABELS))],
            'password' => [$user->exists ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ], attributes: ['name' => 'نام', 'email' => 'ایمیل', 'role' => 'نقش', 'password' => 'رمز عبور']);
    }

    private function isLastAdmin(User $user): bool
    {
        return User::where('role', 'admin')->whereKeyNot($user->id)->doesntExist();
    }
}
