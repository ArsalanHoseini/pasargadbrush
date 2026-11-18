<?php

namespace App\Services\Seo;

use App\Support\WordPressContent;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Article/page HTML (WordPress-imported or written in the admin's editor) as
 * clean Markdown for the .md copies and /llms-full.txt. The HTML is tidied
 * with a real DOM first — never regex on markup — then converted by
 * league/html-to-markdown:
 *
 * - WordPress «[caption]» shortcodes become an image plus an italic caption;
 * - a <p> inside an <li> is unwrapped so lists stay compact;
 * - links and images get absolute URLs; an image whose alt is empty or only a
 *   file name gets the article title instead;
 * - a YouTube <iframe> becomes a watch link, any other iframe a plain link;
 * - <b><strong>…</strong></b> (common in imported posts) stays one bold run;
 * - tables become pipe tables.
 */
class HtmlToMarkdown
{
    private const ROOT_ID = 'markdown-root';

    public function convert(string $html, string $baseUrl, string $fallbackAlt): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument;
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="'.self::ROOT_ID.'">'.WordPressContent::expandCaptions($html).'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        $xpath = new DOMXPath($document);
        $baseUrl = rtrim($baseUrl, '/');

        $this->rewriteImages($xpath, $baseUrl, $fallbackAlt);
        $this->rewriteLinks($xpath, $baseUrl);
        $this->replaceIframes($document, $xpath, $baseUrl);
        $this->flattenFigures($document, $xpath);
        $this->unwrapParagraphsInListItems($xpath);
        $this->collapseNestedBold($xpath);

        $root = $document->getElementById(self::ROOT_ID);
        $innerHtml = '';

        foreach ($root?->childNodes ?? [] as $child) {
            $innerHtml .= $document->saveHTML($child);
        }

        $converter = new HtmlConverter([
            'header_style' => 'atx',
            'strip_tags' => true,
            'remove_nodes' => 'script style noscript',
            'hard_break' => true,
            'strip_placeholder_links' => true,
            'use_autolinks' => false,
        ]);
        $converter->getEnvironment()->addConverter(new TableConverter);

        $markdown = $converter->convert($innerHtml);

        // Non-breaking spaces, soft hyphens (typed in imported posts where a
        // ZWNJ belongs: «برس­ های») and runs of blank lines left by the editor.
        $markdown = str_replace(["\u{00A0}", "\u{00AD} ", "\u{00AD}"], [' ', "\u{200C}", ''], $markdown);
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown) ?? $markdown;

        return trim($markdown);
    }

    private function rewriteImages(DOMXPath $xpath, string $baseUrl, string $fallbackAlt): void
    {
        foreach ($this->elements($xpath, '//img') as $image) {
            $source = $image->getAttribute('src') ?: $image->getAttribute('data-src');

            if ($source === '') {
                $image->parentNode?->removeChild($image);

                continue;
            }

            $alt = trim($image->getAttribute('alt'));

            foreach ($image->attributes ? iterator_to_array($image->attributes) : [] as $attribute) {
                $image->removeAttribute($attribute->nodeName);
            }

            $image->setAttribute('src', $this->absoluteUrl($source, $baseUrl));
            $image->setAttribute('alt', self::isMeaningfulAlt($alt) ? $alt : $fallbackAlt);
        }
    }

    private function rewriteLinks(DOMXPath $xpath, string $baseUrl): void
    {
        foreach ($this->elements($xpath, '//a[@href]') as $link) {
            $link->setAttribute('href', $this->absoluteUrl($link->getAttribute('href'), $baseUrl));
            $link->removeAttribute('title');
        }
    }

    private function replaceIframes(DOMDocument $document, DOMXPath $xpath, string $baseUrl): void
    {
        foreach ($this->elements($xpath, '//iframe') as $iframe) {
            $source = $this->absoluteUrl($iframe->getAttribute('src'), $baseUrl);
            $youtubeId = self::youtubeId($source);

            $paragraph = $document->createElement('p');
            $link = $document->createElement('a');
            $link->setAttribute('href', $youtubeId ? 'https://www.youtube.com/watch?v='.$youtubeId : $source);
            $link->appendChild($document->createTextNode(
                $youtubeId ? 'ویدیو در یوتیوب'.($iframe->getAttribute('title') ? ': '.$iframe->getAttribute('title') : '') : ($iframe->getAttribute('title') ?: $source),
            ));
            $paragraph->appendChild($link);

            $iframe->parentNode?->replaceChild($paragraph, $iframe);
        }
    }

    /**
     * <figure><img><figcaption>…</figcaption></figure> → the image, then the caption in italics.
     */
    private function flattenFigures(DOMDocument $document, DOMXPath $xpath): void
    {
        foreach ($this->elements($xpath, '//figure') as $figure) {
            $replacement = $document->createDocumentFragment();

            foreach (iterator_to_array($figure->childNodes) as $child) {
                if ($child instanceof DOMElement && strtolower($child->nodeName) === 'figcaption') {
                    $caption = trim($child->textContent);

                    if ($caption !== '') {
                        $paragraph = $document->createElement('p');
                        $emphasis = $document->createElement('em');
                        $emphasis->appendChild($document->createTextNode($caption));
                        $paragraph->appendChild($emphasis);
                        $replacement->appendChild($paragraph);
                    }

                    continue;
                }

                if ($child instanceof DOMElement && strtolower($child->nodeName) === 'img') {
                    $paragraph = $document->createElement('p');
                    $paragraph->appendChild($child);
                    $replacement->appendChild($paragraph);

                    continue;
                }

                $replacement->appendChild($child);
            }

            $figure->parentNode?->replaceChild($replacement, $figure);
        }
    }

    /**
     * Editors (TipTap, Quill, WordPress) put a <p> in every <li>; Markdown
     * would turn that into a loose list with blank lines between items.
     */
    private function unwrapParagraphsInListItems(DOMXPath $xpath): void
    {
        foreach ($this->elements($xpath, '//li/p') as $paragraph) {
            $parent = $paragraph->parentNode;

            if (! $parent) {
                continue;
            }

            if ($paragraph->nextSibling !== null && trim($paragraph->nextSibling->textContent) !== '') {
                $parent->insertBefore($paragraph->ownerDocument->createElement('br'), $paragraph->nextSibling);
            }

            while ($paragraph->firstChild) {
                $parent->insertBefore($paragraph->firstChild, $paragraph);
            }

            $parent->removeChild($paragraph);
        }
    }

    private function collapseNestedBold(DOMXPath $xpath): void
    {
        foreach ($this->elements($xpath, '//b|//strong') as $bold) {
            $onlyChild = $this->onlyElementChild($bold);

            if ($onlyChild && in_array(strtolower($onlyChild->nodeName), ['b', 'strong'], true)) {
                while ($onlyChild->firstChild) {
                    $bold->insertBefore($onlyChild->firstChild, $onlyChild);
                }

                $bold->removeChild($onlyChild);
            }
        }
    }

    private function onlyElementChild(DOMElement $element): ?DOMElement
    {
        $found = null;

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                if ($found) {
                    return null;
                }

                $found = $child;
            } elseif (trim($child->textContent) !== '') {
                return null;
            }
        }

        return $found;
    }

    /**
     * @return list<DOMElement>
     */
    private function elements(DOMXPath $xpath, string $query): array
    {
        $nodes = $xpath->query($query);

        return $nodes === false ? [] : array_values(array_filter(
            iterator_to_array($nodes),
            fn (DOMNode $node) => $node instanceof DOMElement,
        ));
    }

    private function absoluteUrl(string $url, string $baseUrl): string
    {
        $url = trim($url);

        if ($url === '' || preg_match('/^([a-z][a-z0-9+.-]*:|#)/i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        return $baseUrl.'/'.ltrim($url, '/');
    }

    /**
     * False for an empty alt or one that is only a file name («IMG_1234.jpg», «Picture2-1024x690»).
     */
    public static function isMeaningfulAlt(?string $alt): bool
    {
        $alt = trim((string) $alt);

        return $alt !== ''
            && ! preg_match('/\.(jpe?g|png|gif|webp|avif|svg)$/i', $alt)
            && ! preg_match('/^[A-Za-z0-9_\-]+$/', $alt);
    }

    public static function youtubeId(string $url): ?string
    {
        if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:embed/|watch\?v=|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $match)) {
            return $match[1];
        }

        return null;
    }
}
