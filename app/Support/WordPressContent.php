<?php

namespace App\Support;

class WordPressContent
{
    /**
     * Opening/closing tags that already form their own block, so the line is kept as-is.
     */
    private const BLOCK_TAG = '/^<\/?(h[1-6]|p|ul|ol|li|table|thead|tbody|tr|td|th|blockquote|figure|figcaption|div|section|hr|pre)\b/i';

    /**
     * Classic-editor WordPress content is stored without <p> tags — WordPress adds
     * them on display (wpautop). This is the simplified equivalent for content
     * we import: each non-empty line that isn't already a block becomes a
     * paragraph. Content's own <h1> is demoted to <h2>, since the page that
     * shows it already has its title as the one <h1>.
     * Already-paragraphed content is returned unchanged (safe to re-run).
     */
    /**
     * Classic WordPress «[caption id=… align=…]<img …> text[/caption]» as a
     * <figure> with a <figcaption> — WordPress expanded the shortcode on
     * display, so stored content still has it. Shortcodes are plain text,
     * not markup, so a pattern is the right tool; the <img> is kept as-is.
     * Content without the shortcode is returned unchanged (safe to re-run).
     */
    public static function expandCaptions(string $content): string
    {
        return preg_replace_callback(
            '/\[caption[^\]]*\](.*?)\[\/caption\]/su',
            function (array $match): string {
                $inner = trim($match[1]);
                $imageEnd = strrpos($inner, '>');

                if ($imageEnd === false) {
                    return $inner;
                }

                $caption = trim(substr($inner, $imageEnd + 1));

                return '<figure>'.substr($inner, 0, $imageEnd + 1)
                    .($caption !== '' ? '<figcaption>'.$caption.'</figcaption>' : '')
                    .'</figure>';
            },
            $content,
        ) ?? $content;
    }

    public static function toHtml(string $content): string
    {
        $content = preg_replace(['/<h1(\b[^>]*)>/i', '/<\/h1>/i'], ['<h2$1>', '</h2>'], $content) ?? $content;

        if (stripos($content, '<p') !== false) {
            return $content;
        }

        $lines = preg_split('/\R/u', $content) ?: [];
        $html = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $html[] = preg_match(self::BLOCK_TAG, $line) ? $line : '<p>'.$line.'</p>';
        }

        return implode("\n", $html);
    }
}
