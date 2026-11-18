<?php

namespace App\Services\WordPressImport;

use App\Services\Seo\HtmlToMarkdown;
use App\Support\WordPressContent;
use DOMDocument;
use DOMXPath;

/**
 * Repairs an imported WordPress post body so it stands on its own:
 *
 * - «[caption]» shortcodes become <figure>/<figcaption>;
 * - every image or link pointing into «…/wp-content/uploads/…» (whatever the
 *   host — the local copy wrote «http://localhost/pasargadbrush/…») is copied
 *   into the media library and the body points at «/storage/…» instead.
 *   A resized copy («Picture1-1024x690.jpg») is replaced by its original;
 *   the site makes its own WebP sizes.
 *
 * URLs are read from the DOM (src/href attributes) and then replaced as
 * exact strings, so the rest of the stored HTML is left byte-for-byte as it was.
 */
class PostBodyRepair
{
    private const UPLOADS_MARKER = '/wp-content/uploads/';

    public function __construct(private MediaCopier $mediaCopier) {}

    /**
     * @return array{body: string, copied: list<string>, missing: list<string>}
     */
    public function repair(string $body, string $uploadsPath, bool $commit, string $fallbackAlt): array
    {
        $body = WordPressContent::expandCaptions($body);
        $copied = [];
        $missing = [];

        foreach ($this->uploadUrls($body) as $url => $alt) {
            $relativePath = rawurldecode(substr($url, strpos($url, self::UPLOADS_MARKER) + strlen(self::UPLOADS_MARKER)));
            $relativePath = strtok($relativePath, '?#') ?: $relativePath;
            $original = preg_replace('/-\d+x\d+(?=\.[A-Za-z0-9]+$)/', '', $relativePath) ?? $relativePath;
            $altText = HtmlToMarkdown::isMeaningfulAlt($alt) ? $alt : $fallbackAlt;

            $source = is_file($this->path($uploadsPath, $original)) ? $original : $relativePath;

            if (! is_file($this->path($uploadsPath, $source))) {
                $missing[] = $relativePath;

                continue;
            }

            $copied[] = $source;

            $media = $this->mediaCopier->copyUploadFile($uploadsPath, $source, $commit, $altText);

            if ($media) {
                $newUrl = '/storage/'.$media->path;
                $body = str_replace([$url, htmlspecialchars($url, ENT_QUOTES)], $newUrl, $body);
            }
        }

        return ['body' => $body, 'copied' => $copied, 'missing' => $missing];
    }

    public static function needsRepair(string $body): bool
    {
        return str_contains($body, '[caption') || str_contains($body, self::UPLOADS_MARKER);
    }

    /**
     * Every distinct uploads URL in an <img src> or <a href>, with the alt
     * text of the image (empty for links).
     *
     * @return array<string, string>
     */
    private function uploadUrls(string $body): array
    {
        if (! str_contains($body, self::UPLOADS_MARKER)) {
            return [];
        }

        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$body.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $urls = [];

        foreach ($xpath->query('//img[@src] | //a[@href]') ?: [] as $element) {
            $url = trim($element->getAttribute($element->nodeName === 'img' ? 'src' : 'href'));

            if (str_contains($url, self::UPLOADS_MARKER)) {
                $urls[$url] = ($urls[$url] ?? '') ?: trim($element->getAttribute('alt'));
            }
        }

        return $urls;
    }

    private function path(string $uploadsPath, string $relativePath): string
    {
        return rtrim($uploadsPath, '\/').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }
}
