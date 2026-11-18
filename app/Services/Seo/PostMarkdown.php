<?php

namespace App\Services\Seo;

use App\Models\BlogPost;
use App\Support\SiteProfile;
use App\Support\WordPressContent;
use Illuminate\Support\Str;

/**
 * An article as a Markdown document (/blog/{slug}.md): YAML frontmatter,
 * the title, then the body converted from HTML.
 */
class PostMarkdown
{
    public function __construct(private HtmlToMarkdown $htmlToMarkdown) {}

    public function render(BlogPost $post): string
    {
        $post->loadMissing(['category', 'tags', 'author']);

        $frontmatter = array_filter([
            'title' => $post->title,
            'description' => self::description($post),
            'url' => route('blog.show', $post->slug),
            'markdown_url' => route('blog.markdown', $post->slug),
            'date_published' => $post->published_at?->toIso8601String(),
            'date_modified' => ($post->updated_at ?? $post->published_at)?->toIso8601String(),
            'author' => self::authorName($post),
            'publisher' => SiteProfile::legalName(),
            'category' => $post->category?->name,
            'tags' => $post->tags->pluck('name')->all() ?: null,
            'language' => config('site.language'),
            'reading_time_minutes' => $post->reading_time,
        ], fn ($value) => $value !== null && $value !== '');

        $yaml = ['---'];

        foreach ($frontmatter as $key => $value) {
            if (is_array($value)) {
                $yaml[] = $key.':';

                foreach ($value as $item) {
                    $yaml[] = '  - '.self::yamlString((string) $item);
                }

                continue;
            }

            $yaml[] = $key.': '.(is_int($value) ? $value : self::yamlString((string) $value));
        }

        $yaml[] = '---';

        $body = $this->htmlToMarkdown->convert(self::normalizedBody($post), SiteProfile::url(), $post->title);

        return implode("\n", $yaml)."\n\n# ".$post->title."\n\n".$body."\n";
    }

    /**
     * The body as WordPress would have displayed it: classic-editor posts
     * were stored without <p> tags (WordPress added them on display).
     */
    public static function normalizedBody(BlogPost $post): string
    {
        return WordPressContent::toHtml((string) $post->body);
    }

    public static function description(BlogPost $post): string
    {
        $text = $post->meta_description ?: $post->excerpt ?: strip_tags((string) $post->body);

        $text = str_replace(["\u{00AD} ", "\u{00AD}"], ["\u{200C}", ''], html_entity_decode(strip_tags($text)));

        return Str::limit(Str::squish($text), 160);
    }

    /**
     * Imported posts have no author; the company is then the author.
     */
    public static function authorName(BlogPost $post): string
    {
        return $post->author?->name ?: SiteProfile::legalName();
    }

    public static function wordCount(BlogPost $post): int
    {
        return count(array_filter(preg_split('/\s+/u', trim(strip_tags((string) $post->body))) ?: []));
    }

    /**
     * A double-quoted YAML scalar — JSON string syntax is valid YAML.
     */
    private static function yamlString(string $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
