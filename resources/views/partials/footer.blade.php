<footer id="contact" class="bg-brand-charcoal text-neutral-300">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <img src="{{ asset('images/logo.png') }}" alt="پاسارگاد برس" class="mb-4 h-9 w-auto">
                <p class="text-sm leading-6 text-neutral-400">
                    شرکت برس صنعتی پاسارگاد — تولیدکننده انواع برس و فرچه صنعتی برای صنایع مختلف.
                </p>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-semibold text-white">دسترسی سریع</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-white">صفحه اصلی</a></li>
                    <li><a href="{{ route('blog.index') }}" class="hover:text-white">مقالات</a></li>
                    @foreach ($navCategories ?? [] as $category)
                        <li><a href="{{ route('categories.show', $category['slug']) }}" class="hover:text-white">{{ $category['name'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h3 class="mb-3 text-sm font-semibold text-white">اطلاعات تماس</h3>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="tel:{{ setting('contact_mobile') }}" class="hover:text-white" dir="ltr">
                            {{ setting('contact_mobile') }}
                        </a>
                    </li>
                    <li>
                        <a href="mailto:{{ setting('contact_email') }}" class="hover:text-white" dir="ltr">
                            {{ setting('contact_email') }}
                        </a>
                    </li>
                    <li class="text-neutral-400">{{ setting('contact_address') }}</li>
                </ul>
            </div>
        </div>

        <div class="mt-10 border-t border-neutral-700 pt-6 text-xs text-neutral-500">
            © {{ now()->format('Y') }} {{ setting('company_legal_name') }}. تمامی حقوق محفوظ است.
        </div>
    </div>
</footer>
