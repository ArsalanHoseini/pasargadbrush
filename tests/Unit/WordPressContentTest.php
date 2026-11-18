<?php

namespace Tests\Unit;

use App\Support\WordPressContent;
use PHPUnit\Framework\TestCase;

class WordPressContentTest extends TestCase
{
    public function test_plain_lines_become_paragraphs_and_blocks_are_kept(): void
    {
        $html = WordPressContent::toHtml("<h1><strong>برس دیسکی</strong></h1>\n<strong>برس دیسکی</strong> پرکاربرد است.\n\n<ul>\n<li>نایلون</li>\n</ul>\nخط آخر");

        $this->assertSame(
            "<h2><strong>برس دیسکی</strong></h2>\n<p><strong>برس دیسکی</strong> پرکاربرد است.</p>\n<ul>\n<li>نایلون</li>\n</ul>\n<p>خط آخر</p>",
            $html,
        );
    }

    public function test_already_paragraphed_content_is_left_alone_apart_from_h1(): void
    {
        $this->assertSame('<p>متن</p><h2>عنوان</h2>', WordPressContent::toHtml('<p>متن</p><h1>عنوان</h1>'));

        $once = WordPressContent::toHtml("یک\nدو");
        $this->assertSame($once, WordPressContent::toHtml($once));
    }
}
