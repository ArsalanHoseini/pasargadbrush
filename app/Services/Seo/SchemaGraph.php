<?php

namespace App\Services\Seo;

/**
 * The page's one JSON-LD graph. Views add their nodes (WebPage, BlogPosting,
 * Product, BreadcrumbList …) while they render; the public layout prints the
 * graph once, with the site-wide Organization and WebSite first, so every
 * node can point at them by «@id» instead of repeating them.
 * Bound per request (scoped); render() also empties it.
 */
class SchemaGraph
{
    /** @var list<array<string, mixed>> */
    private array $nodes = [];

    /**
     * @param  array<string, mixed>  ...$nodes
     */
    public function add(array ...$nodes): void
    {
        foreach ($nodes as $node) {
            $this->nodes[] = $node;
        }
    }

    public function hasWebPage(): bool
    {
        foreach ($this->nodes as $node) {
            if (str_ends_with((string) ($node['@id'] ?? ''), StructuredData::WEBPAGE_FRAGMENT)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The <script type="application/ld+json"> tag. A page that added no
     * WebPage of its own gets a plain one for $url.
     */
    public function render(string $url, string $title, ?string $description): string
    {
        if (! $this->hasWebPage()) {
            $this->add(StructuredData::webPage('WebPage', $url, $title, $description));
        }

        $graph = [
            '@context' => 'https://schema.org',
            '@graph' => [StructuredData::organization(), StructuredData::webSite(), ...$this->nodes],
        ];

        $this->nodes = [];

        return '<script type="application/ld+json">'.self::encode($graph).'</script>';
    }

    /**
     * JSON that is safe inside a <script>: JSON_HEX_TAG writes «<» and «>» as
     * their \u escapes, so a value containing «</script>» cannot end the tag.
     *
     * @param  array<string, mixed>  $data
     */
    public static function encode(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR);
    }
}
