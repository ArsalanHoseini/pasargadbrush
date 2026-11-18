@extends('admin.layout')

@section('title', 'کاربران پنل')

@section('content')
<x-admin.page-header title="کاربران پنل" description="مدیر به همه‌چیز دسترسی دارد؛ ویرایشگر فقط محصولات، دسته‌ها، نظرات، بلاگ، صفحات و مدیا را می‌بیند.">
    <x-slot:actions>
        <x-admin.button :href="route('admin.users.create')" icon="plus">کاربر جدید</x-admin.button>
    </x-slot:actions>
</x-admin.page-header>

<div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
    <table class="w-full text-sm">
        <thead class="border-b border-neutral-200 bg-neutral-50 text-right text-xs text-neutral-500">
            <tr>
                <th class="px-4 py-3 font-medium">نام</th>
                <th class="px-4 py-3 font-medium">ایمیل</th>
                <th class="px-4 py-3 font-medium">نقش</th>
                <th class="px-4 py-3 font-medium">آخرین فعالیت</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @foreach ($users as $user)
                <tr class="hover:bg-neutral-50">
                    <td class="px-4 py-3 font-medium">
                        {{ $user->name }}
                        @if ($user->is(auth()->user()))
                            <span class="mr-1 rounded bg-neutral-100 px-1.5 py-0.5 text-xs font-normal text-neutral-500">شما</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-neutral-500" dir="ltr">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span @class(['rounded-full px-2 py-0.5 text-xs', 'bg-brand-accent/10 text-brand-accent-darker' => $user->isAdmin(), 'bg-neutral-100 text-neutral-600' => ! $user->isAdmin()])>{{ $user->roleLabel() }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-xs text-neutral-500">
                        {{ isset($lastActivity[$user->id]) ? persian_date(\Illuminate\Support\Carbon::parse($lastActivity[$user->id]), \App\Support\PersianDate::COMPACT) : '—' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-left">
                        <a href="{{ route('admin.activity.index', ['user' => $user->id]) }}" class="ml-3 text-neutral-500 hover:text-neutral-900">فعالیت‌ها</a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-neutral-600 hover:text-neutral-900">ویرایش</a>
                        @unless ($user->is(auth()->user()))
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" data-confirm="حساب «{{ $user->name }}» حذف شود؟ این کاربر دیگر نمی‌تواند وارد پنل شود.">
                                @csrf @method('DELETE')
                                <button type="submit" class="mr-2 text-red-600 hover:text-red-800">حذف</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
