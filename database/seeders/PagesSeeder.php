<?php

namespace Database\Seeders;

use App\Actions\Media\StoreUploadedMedia;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

class PagesSeeder extends Seeder
{
    /**
     * "Contact Us": real content comes dynamically from settings — the intro
     * line here is a neutral, true instruction, not fabricated company content.
     *
     * "About Us": real company narrative copied from the owner-provided live
     * page (https://pasargadbrush.com/about-us-2/), sourced verbatim from the
     * local WordPress copy's post_content (page ID 8, "درباره ما") rather than
     * paraphrased — see docs/DECISIONS.md for the two real images and the one
     * inferred unit (% for the two stats that read as percentages in context).
     */
    public function run(): void
    {
        Page::query()->updateOrCreate(
            ['slug' => 'contact-us'],
            [
                'title' => 'تماس با ما',
                'body' => '<p>برای ارتباط با کارشناسان فروش و دریافت مشاوره یا استعلام قیمت، از راه‌های زیر با ما در تماس باشید.</p>',
                'template' => 'contact',
                'status' => 'published',
                'published_at' => now(),
            ],
        );

        Page::query()->updateOrCreate(
            ['slug' => 'about-us'],
            [
                'title' => 'درباره ما',
                'body' => $this->aboutUsBody(),
                'template' => 'about',
                'status' => 'published',
                'published_at' => now(),
            ],
        );
    }

    private function aboutUsBody(): string
    {
        $groupPhoto = $this->copyRealImage('pasargadbrush-group2-1024x739-1.jpg', 'کارخانه و تیم پاسارگاد برس');
        $productPhoto = $this->copyRealImage('بق-min-3.png', 'محصولات برس صنعت پاسارگاد');

        return <<<HTML
<p>شرکت برس صنعت پاسارگاد (P.B.C) در سال ۱۳۷۴ فعالیت خود را در قالب تولید انواع برس‌های صنعتی شروع کرد. این کارخانه در زمینی به مساحت ۳۳۰۰ متر مربع و زیربنای ۱۵۰۰ متر مربع در منطقه پارسه بنا نهاده شد.</p>

<h2>هدف ما</h2>
<p>هدف از تأسیس این شرکت، تغییر ساختار فرچه صنعتی و به‌کارگیری ماشین‌های پیشرفته با تکنولوژی بالا جهت ارتقای کیفی و کمی در این صنعت بوده است.</p>
<p>با توجه به اینکه برس‌های تولیدی در آن زمان بیشتر به‌صورت دستی تولید می‌شد و جوابگوی نیاز بازار نبود، نوعی نارضایتی و بی‌اعتمادی نسبت به محصولات داخلی در کشور به‌وجود آمده بود. خوشبختانه با تولیدات اولیه، برس صنعت پاسارگاد توانست از همان ابتدا اعتماد و رضایت صنعتگران کشور را به‌دست آورد و مانع واردات آن‌ها از کشورهای اروپایی و چین شود.</p>

{$groupPhoto}

<h2>توانمندی تولید</h2>
<p>این کارخانه برس‌سازی اکنون افتخار این را دارد که بیش از پنج‌هزار نمونه برس صنعتی در ابعاد و اشکال مختلف طراحی و تولید کرده و حتی صادرات به کشورهای همسایه و اروپایی را نیز داشته است.</p>

<h2>صنایعی که به آن‌ها خدمت می‌دهیم</h2>
<p>P.B.C برس‌های تولیدی خود را برای مصارف صنایع زیر تولید می‌نماید: صنایع کاشی و سرامیک، نساجی، چوب و ام‌دی‌اف، معادن سنگ و فلز، ماشین‌سازی، شیشه، طلاسازی، دندان‌سازی، آردسازی، ماشین‌آلات بسته‌بندی، هتل‌داری، ماشین‌آلات مکانیزه نظافتی، صنایع غذایی، لوازم خانگی، محصولات شیمیایی، تولید ماشین‌آلات و تجهیزات صنعتی، صنایع تولید مصالح ساختمانی، صنعت چاپ، نفت و پتروشیمی، خودروسازی، تولید محصولات الکترونیکی، سورتینگ میوه، دستگاه‌های واکس برقی، صنایع فولاد و سایر صنایع مرتبط.</p>

{$productPhoto}

<h2>کیفیت و رضایت مشتری</h2>
<p>برس صنعت پاسارگاد با کسب رضایت مشتریان و اصلاح ساختار فرچه‌های صنعتی گامی مؤثر برداشته و توانسته نام خود را در صنعت کشور، با تولیداتی با کیفیت مطلوب، قیمت مناسب و قابل رقابت با محصولات خارجی، به‌عنوان یک تولیدکننده موفق مطرح کند. عرضه محصولات این شرکت به کارخانه‌های بزرگ و نام‌آشنای کشور، گواه این موضوع است.</p>

<ul>
<li>بیش از ۵۰۰۰ نمونه برس صنعتی طراحی و تولید شده</li>
<li>بیش از ۱۱۰ تنوع در خط تولید محصولات</li>
<li>۸۹٪ رضایتمندی مشتری</li>
<li>۹۷٪ تضمین کیفیت محصولات</li>
<li>بیش از ۱۰۰۰ قرارداد موفق با صنایع مختلف</li>
</ul>

<blockquote>«برس صنعت پاسارگاد یکی از شرکت‌های موفق در زمینه تولید انواع برس‌های صنعتی است. من برس نواری و درزگیر را به این شرکت سفارش دادم.» — علی اسماعیلی، مشتری</blockquote>

<p><strong>کیفیت، اعتماد، آینده پاک.</strong></p>
HTML;
    }

    private function copyRealImage(string $filename, string $altText): string
    {
        $existing = Media::query()->where('alt_text', $altText)->first();
        if ($existing) {
            return '<img src="/storage/'.$existing->path.'" alt="'.$altText.'">';
        }

        $uploadsPath = config('wordpress_import.uploads_path');
        $sourceFile = $uploadsPath ? rtrim($uploadsPath, '\\/').'/2023/12/'.$filename : null;

        if (! $sourceFile || ! is_file($sourceFile)) {
            return '';
        }

        $uploaded = new UploadedFile($sourceFile, $filename, mime_content_type($sourceFile), null, true);
        $media = app(StoreUploadedMedia::class)->handle($uploaded, null, $altText);

        return '<img src="/storage/'.$media->path.'" alt="'.$altText.'">';
    }
}
