<?php

/*
|--------------------------------------------------------------------------
| Site profile — the one place for the brand's fixed facts
|--------------------------------------------------------------------------
|
| Everything a page, the JSON-LD graph, /llms.txt and /llms-full.txt say
| about the company comes from here (fixed facts) or from the admin's
| «تنظیمات» (contact details, social links — editable without a deploy),
| both read through App\Support\SiteProfile. Every fact here is taken from
| the site's own content (About Us page, homepage, footer); nothing is
| invented. Unknown facts (opening hours, founding date in Gregorian,
| warranty terms, an FAQ) are deliberately left out — add them here only
| once the owner confirms them.
|
*/

return [

    /*
     * The live address. Generated links use APP_URL (forced in production,
     * see AppServiceProvider); this is the fallback for texts that must name
     * the real site even when rendered elsewhere.
     */
    'production_url' => 'https://pasargadbrush.com',

    'name' => 'پاسارگاد برس',

    'legal_name' => 'شرکت برس صنعت پاسارگاد',

    'english_name' => 'Pasargad Brush',

    /** Names the company is also known by — on the site, the About Us page and the footer. */
    'alternate_names' => [
        'برس پاسارگاد',
        'برس صنعت پاسارگاد',
        'پاسارگاد براش',
        'Pasargad Brush',
        'P.B.C',
    ],

    'tagline' => 'تولیدکننده برس و فرچه صنعتی',

    'description' => [
        'fa' => 'برس پاسارگاد (شرکت برس صنعت پاسارگاد) از سال ۱۳۷۴ تولیدکننده انواع برس و فرچه صنعتی در ایران است: برس ابزاری، قالیشویی و نظافتی، هویج‌شویی و سورتینگ، نواری و درزگیر، صنایع غذایی و برس‌های سفارشی با ابعاد دلخواه — فروش مستقیم از کارخانه و بدون واسطه.',
        'en' => 'Pasargad Brush (Pasargad Brush Industry Co., "P.B.C") has manufactured industrial brushes in Iran since 1374 in the Iranian calendar (mid-1990s). The factory makes tool brushes, carpet-washing and cleaning brushes, vegetable-washing and sorting brushes, strip and seal brushes, food-industry brushes and custom brushes built to the customer\'s exact dimensions, and sells directly from the factory.',
    ],

    /** Shown as «از سال ۱۳۷۴» — the Solar Hijri year from the About Us page. */
    'founded_year_shamsi' => '۱۳۷۴',

    'logo' => 'images/logo.png',

    'language' => 'fa-IR',

    /**
     * Structured parts of the factory address. The street line itself is the
     * admin setting «contact_address» (تخت جمشید، شهرک صنعتی انجیره مرودشت).
     */
    'address' => [
        'locality' => 'مرودشت',
        'region' => 'فارس',
        'country' => 'IR',
    ],

    /** From the About Us page («توانمندی تولید» and the opening paragraph). */
    'facts' => [
        'کارخانه در زمینی به مساحت ۳۳۰۰ متر مربع و زیربنای ۱۵۰۰ متر مربع در منطقه پارسه (مرودشت، فارس)',
        'بیش از ۵۰۰۰ نمونه برس صنعتی در ابعاد و اشکال مختلف طراحی و تولید شده',
        'صادرات به کشورهای همسایه و اروپایی',
    ],

    /** The homepage «چرا پاسارگاد برس؟» cards. */
    'highlights' => [
        ['title' => 'ساخت سفارشی', 'text' => 'محصولات بر اساس ابعاد، نوع تار و کاربرد دقیق شما تولید می‌شوند.'],
        ['title' => 'تنوع کاربردی', 'text' => 'از صنایع غذایی و نساجی تا نفت، پتروشیمی و قالیشویی.'],
        ['title' => 'ارتباط مستقیم با تولیدکننده', 'text' => 'مشاوره و استعلام قیمت بدون واسطه، مستقیم با تیم فنی.'],
    ],

    /** «صنایعی که به آن‌ها خدمت می‌دهیم» on the About Us page. */
    'industries' => [
        'کاشی و سرامیک', 'نساجی', 'چوب و ام‌دی‌اف', 'معادن سنگ و فلز', 'ماشین‌سازی', 'شیشه', 'طلاسازی',
        'دندان‌سازی', 'آردسازی', 'ماشین‌آلات بسته‌بندی', 'هتل‌داری', 'ماشین‌آلات مکانیزه نظافتی', 'صنایع غذایی',
        'لوازم خانگی', 'محصولات شیمیایی', 'مصالح ساختمانی', 'صنعت چاپ', 'نفت و پتروشیمی', 'خودروسازی',
        'محصولات الکترونیکی', 'سورتینگ میوه', 'دستگاه‌های واکس برقی', 'فولاد',
    ],

    /**
     * How buying works (docs/DECISIONS.md: hybrid catalog). Prices themselves
     * come from the products' variants, never from here.
     */
    'buying' => [
        'audience' => 'کارخانه‌ها و صنایعی که برس صنعتی مصرف می‌کنند، قالیشویی‌ها و کارواش‌ها',
        'pricing' => 'برخی محصولات قیمت ثابت برای هر سایز دارند (به تومان، روی صفحه محصول). بیشتر محصولات سفارشی‌اند: مشتری ابعاد را ثبت می‌کند و قیمت پس از بررسی کارشناس اعلام می‌شود.',
        'ordering' => 'سفارش آنلاین از صفحه هر محصول (افزودن به سبد یا درخواست قیمت)، یا تماس تلفنی و واتساپ.',
        'steps' => [
            'محصول مناسب را از دسته‌بندی‌ها انتخاب کنید (یا با تیم فنی مشورت کنید).',
            'برای محصولات سفارشی، ابعاد خواسته‌شده روی نقشه فنی محصول را وارد کنید.',
            'سفارش یا درخواست قیمت را ثبت کنید؛ قیمت محصولات سفارشی پس از بررسی اعلام می‌شود.',
            'وضعیت سفارش را از «پیگیری سفارش» یا حساب کاربری دنبال کنید.',
        ],
    ],

    /*
     * Paths no crawler should fetch — shared by every robots.txt group,
     * because a bot with its own group ignores the «*» group entirely.
     */
    'private_paths' => [
        '/admin',
        '/account',
        '/login',
        '/cart',
        '/checkout',
        '/order-tracking',
    ],

    /*
     * AI crawlers named in robots.txt. To block one, move its name to
     * «blocked»; it then gets «Disallow: /».
     */
    'ai_crawlers' => [
        /* Fetch pages to answer a user's question or show the site in AI search results. */
        'answer' => [
            'OAI-SearchBot', 'ChatGPT-User', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot',
            'Perplexity-User', 'DuckAssistBot', 'MistralAI-User', 'YouBot',
        ],
        /* Collect pages to train models. */
        'training' => [
            'GPTBot', 'ClaudeBot', 'anthropic-ai', 'Google-Extended', 'Applebot-Extended',
            'Meta-ExternalAgent', 'Amazonbot', 'CCBot', 'cohere-ai', 'Bytespider',
        ],
        'blocked' => [],
    ],

    /** How many articles /llms-full.txt carries in full. */
    'llms_full_post_limit' => 50,

];
