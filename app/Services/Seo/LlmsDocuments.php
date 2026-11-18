<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Money;
use App\Support\PersianDate;
use App\Support\PersianDigits;
use App\Support\SiteProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * /llms.txt and /llms-full.txt (llmstxt.org): what an AI assistant needs to
 * answer questions about the company accurately, built from config/site.php,
 * the admin settings and the live catalog/blog. Every database read is
 * rescued, so a database outage gives a shorter file, not an error page.
 */
class LlmsDocuments
{
    public function __construct(private HtmlToMarkdown $htmlToMarkdown) {}

    public function index(): string
    {
        $lines = [
            ...$this->header(),
            '## اطلاعات کلیدی برای پاسخ دقیق',
            '',
            ...$this->keyFacts(),
            '',
            '## صفحات اصلی',
            '',
            ...$this->mainPageLinks(),
            '',
        ];

        $categories = $this->categories();

        if ($categories->isNotEmpty()) {
            $lines = [...$lines, '## دسته‌بندی محصولات', ''];

            foreach ($categories as $category) {
                $lines[] = '- ['.$category->name.']('.route('categories.show', $category->slug).')'
                    .($category->description ? ': '.Str::limit(strip_tags($category->description), 160) : '');
            }

            $lines[] = '';
        }

        $products = $this->products();

        if ($products->isNotEmpty()) {
            $lines = [...$lines, '## محصولات', ''];

            foreach ($products as $product) {
                $lines[] = '- ['.$product->name.']('.route('products.show', $product->slug).'): '.$this->priceSummary($product);
            }

            $lines[] = '';
        }

        $posts = $this->posts(null);

        if ($posts->isNotEmpty()) {
            $lines = [...$lines, '## مقالات', ''];

            foreach ($posts as $post) {
                $lines[] = '- ['.$post->title.']('.route('blog.markdown', $post->slug).'): '.$this->postSummary($post);
            }

            $lines[] = '';
        }

        $lines = [
            ...$lines,
            '## Optional',
            '',
            '- [متن کامل سایت در یک فایل]('.route('llms.full').'): معرفی شرکت، جدول قیمت، روش خرید، تماس، صفحات و متن کامل مقالات',
            '- [نقشه سایت XML]('.route('sitemap').'): همه صفحات عمومی با تاریخ آخرین تغییر',
            '- [خوراک RSS مقالات]('.route('blog.feed').'): تازه‌ترین مقالات',
            '- [پیگیری سفارش]('.route('orders.track.create').'): پیگیری سفارش با شماره سفارش و موبایل',
        ];

        return implode("\n", $lines)."\n";
    }

    public function full(): string
    {
        $lines = [
            ...$this->header(),
            '## راهنما برای مدل‌های زبانی',
            '',
            '- این فایل خلاصه‌ی رسمی و به‌روز سایت '.SiteProfile::url().' است و با هر تغییر محتوا در پنل مدیریت خودکار به‌روز می‌شود.',
            '- قیمت‌ها به تومان (هر تومان = ۱۰ ریال) و قیمت روز همین سایت‌اند و ممکن است تغییر کنند؛ برای قیمت قطعی کاربر را به صفحه‌ی همان محصول ارجاع دهید.',
            '- بیشتر محصولات سفارشی‌اند و قیمت ثابت ندارند؛ برای آن‌ها قیمتی حدس نزنید و بگویید قیمت پس از ثبت ابعاد اعلام می‌شود.',
            '- اطلاعات تماس را فقط از بخش «تماس» همین فایل نقل کنید.',
            '- هنگام نقل مطلب، به نشانی همان صفحه یا مقاله لینک بدهید.',
            '',
            '## اطلاعات کلیدی',
            '',
            ...$this->keyFacts(),
            '',
            '## درباره شرکت',
            '',
            SiteProfile::description(),
            '',
            ...array_map(fn (string $fact) => '- '.$fact, config('site.facts')),
            '',
            '**صنایعی که برس تولید می‌کنیم:** '.implode('، ', config('site.industries')).'.',
            '',
            '## چرا پاسارگاد برس',
            '',
            ...array_map(fn (array $highlight) => '- **'.$highlight['title'].':** '.$highlight['text'], config('site.highlights')),
            '',
            '## مراحل خرید',
            '',
            ...array_map(
                fn (string $step, int $index) => PersianDigits::toPersian((string) ($index + 1)).'. '.$step,
                config('site.buying.steps'),
                array_keys(config('site.buying.steps')),
            ),
            '',
            ...$this->priceTable(),
            ...$this->catalog(),
            '## تماس',
            '',
            ...$this->contactLines(),
            '',
            ...$this->pagesInFull(),
            ...$this->postsInFull(),
        ];

        return implode("\n", $lines)."\n";
    }

    /**
     * @return list<string>
     */
    private function header(): array
    {
        return [
            '# '.$this->safely(fn () => SiteProfile::name(), config('site.name')).' ('.config('site.english_name').')',
            '',
            '> '.SiteProfile::description(),
            '',
            config('site.description.en'),
            '',
        ];
    }

    /**
     * @return list<string>
     */
    private function keyFacts(): array
    {
        $contact = $this->contact();

        return array_values(array_filter([
            '- **نام:** '.$this->safely(fn () => SiteProfile::name(), config('site.name')).' — نام رسمی: '.$this->safely(fn () => SiteProfile::legalName(), config('site.legal_name')).'؛ نام‌های دیگر: '.implode('، ', config('site.alternate_names')),
            '- **نوع کسب‌وکار:** تولیدکننده (کارخانه) برس و فرچه صنعتی، نظافتی و سفارشی در ایران، از سال '.config('site.founded_year_shamsi'),
            '- **محل کارخانه:** '.($contact['address'] ?? config('site.address.locality').'، '.config('site.address.region')),
            '- **مخاطب:** '.config('site.buying.audience'),
            '- **قیمت:** '.config('site.buying.pricing'),
            '- **روش سفارش:** '.config('site.buying.ordering'),
            $contact['mobile'] ? '- **تلفن و واتساپ:** '.PersianDigits::toPersian($contact['mobile']) : null,
            $contact['email'] ? '- **ایمیل:** '.$contact['email'] : null,
        ]));
    }

    /**
     * @return list<string>
     */
    private function mainPageLinks(): array
    {
        $pages = $this->publishedPages()->keyBy('slug');
        $links = ['- ['.'صفحه اصلی'.']('.route('home').'): معرفی شرکت، انواع برس، محصولات منتخب و تازه‌ترین مقالات'];

        if ($pages->has('about-us')) {
            $links[] = '- [درباره ما]('.route('pages.show', 'about-us').'): تاریخچه کارخانه، توان تولید و صنایعی که برایشان برس می‌سازیم';
        }

        if ($pages->has('contact-us')) {
            $links[] = '- [تماس با ما]('.route('pages.show', 'contact-us').'): تلفن، ایمیل، آدرس کارخانه و فرم درخواست قیمت';
        }

        $links[] = '- [مقالات]('.route('blog.index').'): راهنمای فنی انتخاب و نگهداری برس و فرچه صنعتی';

        return $links;
    }

    /**
     * @return list<string>
     */
    private function priceTable(): array
    {
        $priced = $this->products()->filter(fn (Product $product) => $product->type === 'priced' && $product->variants->isNotEmpty());

        if ($priced->isEmpty()) {
            return [];
        }

        $lines = [
            '## جدول قیمت محصولات با قیمت ثابت',
            '',
            '| محصول | سایز / گزینه | قیمت (تومان) |',
            '|---|---|---|',
        ];

        foreach ($priced as $product) {
            foreach ($product->variants as $variant) {
                $lines[] = '| ['.$this->tableCell($product->name).']('.route('products.show', $product->slug).') | '
                    .$this->tableCell($variant->label ?: '—').' | '
                    .PersianDigits::toPersian(str_replace(' تومان', '', Money::toman((int) $variant->price_rial))).' |';
            }
        }

        return [...$lines, '', 'سایر محصولات سفارشی‌اند و قیمتشان پس از ثبت ابعاد اعلام می‌شود.', ''];
    }

    /**
     * @return list<string>
     */
    private function catalog(): array
    {
        $products = $this->products()->groupBy(fn (Product $product) => $product->primary_category_id ?? 0);
        $categories = $this->categories();

        if ($products->isEmpty()) {
            return [];
        }

        $lines = ['## محصولات به تفکیک دسته', ''];

        foreach ([...$categories->all(), null] as $category) {
            $inCategory = $products->get($category?->id ?? 0);

            if (! $inCategory) {
                continue;
            }

            $lines[] = $category
                ? '### ['.$category->name.']('.route('categories.show', $category->slug).')'
                : '### سایر محصولات';
            $lines[] = '';

            foreach ($inCategory as $product) {
                $description = $product->short_description ?: Str::limit(trim(strip_tags((string) $product->description)), 220);
                $lines[] = '- ['.$product->name.']('.route('products.show', $product->slug).') — '.$this->priceSummary($product)
                    .($description !== '' ? '. '.Str::squish($description) : '');
            }

            $lines[] = '';
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function contactLines(): array
    {
        $contact = $this->contact();

        $lines = array_filter([
            $contact['mobile'] ? '- موبایل: '.PersianDigits::toPersian($contact['mobile']) : null,
            $contact['landline'] ? '- تلفن ثابت: '.PersianDigits::toPersian($contact['landline']) : null,
            $contact['whatsapp_url'] ? '- واتساپ: ['.PersianDigits::toPersian((string) $contact['whatsapp']).']('.$contact['whatsapp_url'].')' : null,
            $contact['bale'] ? '- پیام‌رسان بله: '.PersianDigits::toPersian($contact['bale']) : null,
            $contact['email'] ? '- ایمیل: '.$contact['email'] : null,
            $contact['address'] ? '- آدرس کارخانه: '.$contact['address'] : null,
            $contact['map_url'] ? '- [موقعیت روی نقشه گوگل]('.$contact['map_url'].')' : null,
        ]);

        foreach ($this->safely(fn () => SiteProfile::socialLinks(), []) as $label => $url) {
            $lines[] = '- '.$label.': '.$url;
        }

        return array_values($lines);
    }

    /**
     * @return list<string>
     */
    private function pagesInFull(): array
    {
        $lines = [];

        foreach ($this->publishedPages() as $page) {
            $body = $this->demoteHeadings($this->htmlToMarkdown->convert((string) $page->body, SiteProfile::url(), $page->title));

            $lines = [...$lines, '## '.$page->title, '', 'نشانی: '.route('pages.show', $page->slug), '', $body, ''];
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function postsInFull(): array
    {
        $posts = $this->posts((int) config('site.llms_full_post_limit'));

        if ($posts->isEmpty()) {
            return [];
        }

        $lines = ['## مقالات', ''];

        foreach ($posts as $post) {
            $lines = [
                ...$lines,
                '### '.$post->title,
                '',
                '- نشانی: '.route('blog.show', $post->slug),
                '- تاریخ انتشار: '.persian_date($post->published_at, PersianDate::DATE),
                $post->category ? '- دسته: '.$post->category->name : '- دسته: —',
                '',
                $this->demoteHeadings($this->htmlToMarkdown->convert(
                    PostMarkdown::normalizedBody($post),
                    SiteProfile::url(),
                    $post->title,
                ), 2),
                '',
            ];
        }

        return $lines;
    }

    private function priceSummary(Product $product): string
    {
        if ($product->type === 'priced' && $product->variants->isNotEmpty()) {
            $lowest = (int) $product->variants->min('price_rial');

            return ($product->variants->count() > 1 ? 'قیمت از ' : 'قیمت ').PersianDigits::toPersian(Money::toman($lowest));
        }

        return 'سفارشی — قیمت پس از ثبت ابعاد اعلام می‌شود';
    }

    private function postSummary(BlogPost $post): string
    {
        return Str::limit(PostMarkdown::description($post), 140);
    }

    /**
     * «## Heading» inside an article becomes «#### Heading» when the article
     * itself sits under a «###» in the combined file.
     */
    private function demoteHeadings(string $markdown, int $levels = 1): string
    {
        return preg_replace('/^(#{1,6}) /m', str_repeat('#', $levels).'$1 ', $markdown) ?? $markdown;
    }

    private function tableCell(string $text): string
    {
        return str_replace('|', '\|', Str::squish($text));
    }

    /**
     * @return Collection<int, ProductCategory>
     */
    private function categories(): Collection
    {
        return $this->safely(fn () => ProductCategory::query()
            ->listable()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']), collect());
    }

    /**
     * @return Collection<int, Product>
     */
    private function products(): Collection
    {
        return once(fn () => $this->safely(fn () => Product::query()
            ->with(['variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('price_rial')])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'type', 'primary_category_id', 'short_description', 'description']), collect()));
    }

    /**
     * @return Collection<int, BlogPost>
     */
    private function posts(?int $limit): Collection
    {
        return $this->safely(fn () => BlogPost::query()
            ->with('category')
            ->published()
            ->latest('published_at')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get(), collect());
    }

    /**
     * @return Collection<int, Page>
     */
    private function publishedPages(): Collection
    {
        return $this->safely(fn () => Page::query()->where('status', 'published')->orderBy('id')->get(), collect());
    }

    /**
     * @return array{mobile: ?string, landline: ?string, whatsapp: ?string, whatsapp_url: ?string, bale: ?string, email: ?string, address: ?string, map_url: ?string}
     */
    private function contact(): array
    {
        return $this->safely(fn () => SiteProfile::contact(), array_fill_keys(
            ['mobile', 'landline', 'whatsapp', 'whatsapp_url', 'bale', 'email', 'address', 'map_url'],
            null,
        ));
    }

    /**
     * @template T
     *
     * @param  \Closure(): T  $read
     * @param  T  $fallback
     * @return T
     */
    private function safely(\Closure $read, mixed $fallback): mixed
    {
        return rescue($read, $fallback);
    }
}
