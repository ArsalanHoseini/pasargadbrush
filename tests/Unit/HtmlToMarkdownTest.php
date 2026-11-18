<?php

namespace Tests\Unit;

use App\Services\Seo\HtmlToMarkdown;
use PHPUnit\Framework\TestCase;

class HtmlToMarkdownTest extends TestCase
{
    private const BASE = 'https://pasargadbrush.com';

    private function convert(string $html, string $fallbackAlt = 'عنوان مقاله'): string
    {
        return (new HtmlToMarkdown)->convert($html, self::BASE, $fallbackAlt);
    }

    public function test_headings_bold_and_links_become_markdown_with_absolute_urls(): void
    {
        $markdown = $this->convert('<h2>عنوان</h2><p style="font-weight: 400;"><b><strong>پررنگ</strong></b> و <a href="/products/x">لینک</a></p>');

        $this->assertSame("## عنوان\n\n**پررنگ** و [لینک](https://pasargadbrush.com/products/x)", $markdown);
    }

    public function test_wordpress_caption_shortcode_becomes_image_and_caption_with_alt_fallback(): void
    {
        $markdown = $this->convert('[caption id="attachment_1" align="aligncenter" width="1024"]<img class="wp-image-1" src="/storage/a.jpg" alt="Picture2-1024x690" width="1024" /> زیرنویس تصویر[/caption]');

        $this->assertSame("![عنوان مقاله](https://pasargadbrush.com/storage/a.jpg)\n\n*زیرنویس تصویر*", $markdown);
    }

    public function test_paragraphs_inside_list_items_keep_the_list_compact(): void
    {
        $markdown = $this->convert('<ul><li><p>یک</p></li><li><p>دو</p></li></ul>');

        $this->assertSame("- یک\n- دو", $markdown);
    }

    public function test_tables_become_pipe_tables(): void
    {
        $markdown = $this->convert('<table><thead><tr><th>سایز</th><th>قیمت</th></tr></thead><tbody><tr><td>۱۰</td><td>۲۰</td></tr></tbody></table>');

        $this->assertSame("| سایز | قیمت |\n|---|---|\n| ۱۰ | ۲۰ |", $markdown);
    }

    public function test_youtube_iframe_becomes_a_watch_link(): void
    {
        $markdown = $this->convert('<p><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="معرفی"></iframe></p>');

        $this->assertSame('[ویدیو در یوتیوب: معرفی](https://www.youtube.com/watch?v=dQw4w9WgXcQ)', $markdown);
    }

    public function test_meaningful_alt_text_is_kept_and_protocol_relative_urls_get_https(): void
    {
        $this->assertSame('![برس دیسکی](https://cdn.example/b.png)', $this->convert('<img src="//cdn.example/b.png" alt="برس دیسکی">'));
    }

    public function test_soft_hyphens_from_imported_posts_become_zero_width_non_joiners(): void
    {
        $this->assertSame("برس\u{200C}های صنعتی", $this->convert("<p>برس\u{00AD} های صنعتی</p>"));
    }

    public function test_file_name_alt_texts_are_not_meaningful(): void
    {
        $this->assertFalse(HtmlToMarkdown::isMeaningfulAlt(''));
        $this->assertFalse(HtmlToMarkdown::isMeaningfulAlt('IMG_1234.jpg'));
        $this->assertFalse(HtmlToMarkdown::isMeaningfulAlt('Picture2-1024x690'));
        $this->assertTrue(HtmlToMarkdown::isMeaningfulAlt('استریپ براش'));
    }
}
