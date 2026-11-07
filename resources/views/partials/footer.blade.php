{{-- Rebuilt from the previous WordPress footer (Elementor template «footer», owner request 2026-11-07):
     photo background, «درباره ما» block, contact info with gold icons, quick links, social icons,
     the «کیفیت، اعتماد، آینده پاک» strap line — plus the factory map. --}}
@php
    $whatsappUrl = whatsapp_url(setting('contact_whatsapp'));
    $socialLinks = array_filter([
        'واتساپ' => $whatsappUrl,
        'اینستاگرام' => setting('social_instagram_url'),
        'تلگرام' => setting('social_telegram_url'),
        'لینکدین' => setting('social_linkedin_url'),
        'یوتیوب' => setting('social_youtube_url'),
    ]);
@endphp
<footer id="contact" class="relative isolate overflow-hidden bg-brand-charcoal-deep text-white">
    <img src="{{ asset('images/footer-bg.webp') }}"
         srcset="{{ asset('images/footer-bg-768.webp') }} 768w, {{ asset('images/footer-bg.webp') }} 1536w"
         sizes="100vw" width="1536" height="1024" alt="" loading="lazy" decoding="async"
         class="absolute inset-0 -z-10 h-full w-full object-cover object-left opacity-60">
    {{-- Dark on the right, where the RTL text sits; the brush photo stays visible on the left. --}}
    <div class="absolute inset-0 -z-10 bg-gradient-to-l from-brand-charcoal-deep from-55% via-brand-charcoal-deep/90 to-black/20"></div>

    {{-- About --}}
    <div class="mx-auto max-w-6xl px-4 pt-14 pb-10 sm:px-6">
        <div class="max-w-xl">
            <p class="mb-2 text-[11px] font-semibold tracking-[0.3em] text-white/80" dir="ltr">ABOUT US</p>
            <span class="mb-4 block h-0.5 w-12 bg-brand-gold"></span>
            <h2 class="mb-4 text-3xl font-bold sm:text-4xl">درباره <span class="text-brand-gold">ما</span></h2>
            <p class="mb-6 leading-8 text-white/90 sm:text-lg">
                پاسارگاد براش با تکیه بر دانش فنی، تجربه و تعهد به کیفیت، یکی از تولیدکنندگان پیشرو در زمینه تولید انواع برس و فرچه صنعتی و تخصصی در ایران است. ما با ارائه محصولات بادوام و متناسب با نیاز صنایع مختلف، در کنار شما هستیم تا راه‌حل‌های مطمئن و کارآمد برای چالش‌های صنعتی شما فراهم کنیم.
            </p>
            <a href="{{ route('pages.show', 'contact-us') }}"
               class="inline-flex items-center gap-2 rounded-full border border-brand-gold px-6 py-2.5 text-sm font-semibold text-brand-gold transition hover:bg-brand-gold hover:text-neutral-950">
                ارتباط با ما
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </div>

    <div class="bg-black/55">
        <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
            {{-- Contact --}}
            <div class="lg:col-span-1">
                <h3 class="mb-2 font-semibold">اطلاعات تماس</h3>
                <span class="mb-5 block h-0.5 w-10 bg-brand-gold"></span>
                <ul class="space-y-4 text-sm leading-6">
                    @if (setting('contact_address'))
                        <li class="flex gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0119 9.5C19 14.8 12 21 12 21z" stroke-linejoin="round"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                            <span>{{ setting('contact_address') }}</span>
                        </li>
                    @endif
                    @if (setting('contact_mobile'))
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-brand-gold" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.6a1 1 0 01-.25 1z"/></svg>
                            <a href="tel:{{ setting('contact_mobile') }}" class="hover:text-brand-gold" dir="ltr">{{ setting('contact_mobile') }}</a>
                        </li>
                    @endif
                    @if ($whatsappUrl)
                        <li class="flex items-center gap-3">
                            @include('partials.icons.whatsapp', ['class' => 'h-5 w-5 shrink-0 text-brand-gold'])
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="hover:text-brand-gold">
                                واتساپ: <span dir="ltr">{{ setting('contact_whatsapp') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (setting('contact_bale'))
                        <li class="flex items-center gap-3">
                            @include('partials.icons.bale', ['class' => 'h-5 w-5 shrink-0 text-brand-gold'])
                            <span>بله: <span dir="ltr">{{ setting('contact_bale') }}</span></span>
                        </li>
                    @endif
                    @if (setting('contact_email'))
                        <li class="flex items-center gap-3">
                            <svg class="h-5 w-5 shrink-0 text-brand-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6" stroke-linejoin="round"/></svg>
                            <a href="mailto:{{ setting('contact_email') }}" class="hover:text-brand-gold" dir="ltr">{{ setting('contact_email') }}</a>
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Quick links --}}
            <div>
                <h3 class="mb-2 font-semibold">دسترسی سریع</h3>
                <span class="mb-5 block h-0.5 w-10 bg-brand-gold"></span>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-brand-gold">صفحه اصلی</a></li>
                    <li><a href="{{ route('blog.index') }}" class="hover:text-brand-gold">مقالات</a></li>
                    <li><a href="{{ route('pages.show', 'about-us') }}" class="hover:text-brand-gold">درباره ما</a></li>
                    <li><a href="{{ route('pages.show', 'contact-us') }}" class="hover:text-brand-gold">تماس با ما</a></li>
                    <li><a href="{{ route('orders.track.create') }}" class="hover:text-brand-gold">پیگیری سفارش</a></li>
                </ul>

                @if ($socialLinks)
                    <h3 class="mt-8 mb-2 font-semibold">ما را دنبال کنید</h3>
                    <span class="mb-5 block h-0.5 w-10 bg-brand-gold"></span>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($socialLinks as $label => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}" title="{{ $label }}"
                               class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-neutral-800 transition hover:bg-brand-gold">
                                @include('partials.icons.social', ['network' => $label, 'class' => 'h-5 w-5'])
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Map --}}
            @if (setting('map_embed_url'))
                <div class="sm:col-span-2">
                    <h3 class="mb-2 font-semibold">موقعیت کارخانه</h3>
                    <span class="mb-5 block h-0.5 w-10 bg-brand-gold"></span>
                    <div class="relative aspect-[16/9] overflow-hidden rounded-xl bg-neutral-800 ring-1 ring-white/10">
                        <iframe src="{{ setting('map_embed_url') }}" title="موقعیت کارخانه برس پاسارگاد روی نقشه"
                                class="h-full w-full border-0" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" tabindex="-1"></iframe>
                        @if (setting('map_link_url'))
                            {{-- Covers the embed so one click opens the location in Google Maps (owner request). --}}
                            <a href="{{ setting('map_link_url') }}" target="_blank" rel="noopener"
                               class="group absolute inset-0 flex items-end justify-start p-3">
                                <span class="rounded-full bg-neutral-950/80 px-4 py-2 text-xs font-medium text-white shadow transition group-hover:bg-brand-gold group-hover:text-neutral-950">
                                    مشاهده و مسیریابی در گوگل‌مپ
                                </span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <div class="border-t border-white/10">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-5 text-xs text-white/80 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="font-medium text-white">کیفیت، اعتماد، آینده پاک</p>
                <p>© {{ persian_date(now(), 'y') }} {{ setting('company_legal_name') }} — <span dir="ltr">Pasargad Brush. All Rights Reserved</span></p>
            </div>
        </div>
    </div>
</footer>
