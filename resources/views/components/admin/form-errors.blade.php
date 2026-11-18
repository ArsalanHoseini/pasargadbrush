{{-- Validation summary at the top of an edit form. --}}
@if ($errors->any())
    <div class="mb-6 flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
        <x-admin.icon name="exclamation" class="text-red-600" />
        <div>
            <p class="mb-1 font-medium">لطفاً این موارد را اصلاح کنید:</p>
            <ul class="list-disc space-y-0.5 pr-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
