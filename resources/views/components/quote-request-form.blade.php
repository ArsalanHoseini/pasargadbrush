@props(['product' => null])

@php $accountCustomer = auth('customer')->user(); @endphp

<div id="quote-form" class="rounded-lg border border-neutral-200 bg-white p-6">
    @if (session('quote_request_sent'))
        <div class="mb-4 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700">
            درخواست شما با موفقیت ثبت شد. در اسرع وقت با شما تماس خواهیم گرفت.
        </div>
    @else
        <h2 class="mb-4 text-sm font-bold text-neutral-900">درخواست استعلام قیمت</h2>

        @if ($errors->any())
            <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('quote-requests.store') }}" class="space-y-3">
            @csrf
            @if ($product)
                <input type="hidden" name="product_id" value="{{ $product->id }}">
            @endif

            {{-- Honeypot: hidden from real visitors via CSS, not aria-hidden (so it stays
                 out of a screen reader's form flow without relying on JS). --}}
            <div class="absolute h-0 w-0 overflow-hidden opacity-0" aria-hidden="true">
                <label>وب‌سایت<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <input name="name" placeholder="نام و نام خانوادگی *" value="{{ old('name', $accountCustomer?->name) }}" required
                       class="rounded border border-neutral-300 px-3 py-2 text-sm">
                <input name="phone" placeholder="شماره تماس *" value="{{ old('phone', $accountCustomer?->phone) }}" required dir="ltr"
                       class="rounded border border-neutral-300 px-3 py-2 text-sm">
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <input name="company" placeholder="نام شرکت (اختیاری)" value="{{ old('company', $accountCustomer?->company_name) }}"
                       class="rounded border border-neutral-300 px-3 py-2 text-sm">
                <input name="requested_quantity" placeholder="تیراژ/تعداد درخواستی (اختیاری)" value="{{ old('requested_quantity') }}"
                       class="rounded border border-neutral-300 px-3 py-2 text-sm">
            </div>
            @if ($product?->requiresDimensions())
                <div class="rounded border border-neutral-200 bg-neutral-50 p-3">
                    <p class="mb-3 text-sm font-medium text-neutral-800">ابعاد مورد نیاز</p>
                    <x-dimension-fields :product="$product" :with-notes="false" />
                </div>
            @endif
            <input name="email" type="email" placeholder="ایمیل (اختیاری)" value="{{ old('email', $accountCustomer?->email) }}" dir="ltr"
                   class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            <textarea name="message" rows="4" placeholder="توضیح نیاز شما (ابعاد، مشخصات، کاربرد) *" required
                      class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('message') }}</textarea>

            <button type="submit" class="w-full rounded-full bg-brand-accent-dark px-6 py-3 text-sm font-semibold text-white hover:bg-brand-accent-darker">
                ارسال درخواست
            </button>
        </form>
    @endif
</div>
