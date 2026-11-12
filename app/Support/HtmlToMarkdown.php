<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

final class HtmlToMarkdown
{
    public static function convert(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return trim(strip_tags($html));
        }

        return trim((string) preg_replace("/\n{3,}/", "\n\n", self::children($body)));
    }

    private static function children(DOMNode $node): string
    {
        $markdown = '';

        foreach ($node->childNodes as $child) {
            $markdown .= self::node($child);
        }

        return $markdown;
    }

    private static function node(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return (string) preg_replace('/\s+/', ' ', (string) $node->nodeValue);
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $inner = self::children($node);
        $text = trim($inner);

        return match (strtolower($node->nodeName)) {
            'h1' => "\n\n# {$text}\n\n",
            'h2' => "\n\n## {$text}\n\n",
            'h3' => "\n\n### {$text}\n\n",
            'h4', 'h5', 'h6' => "\n\n#### {$text}\n\n",
            'p', 'div' => $text === '' ? "\n\n" : "\n\n{$text}\n\n",
            'br' => "  \n",
            'hr' => "\n\n---\n\n",
            'strong', 'b' => $text === '' ? '' : "**{$text}**",
            'em', 'i' => $text === '' ? '' : "*{$text}*",
            's' => $text === '' ? '' : "~~{$text}~~",
            'code' => "`{$text}`",
            'pre' => "\n\n```\n".trim($node->textContent)."\n```\n\n",
            'blockquote' => "\n\n> ".str_replace("\n", "\n> ", $text)."\n\n",
            'li' => $node->parentNode?->nodeName === 'ol' ? "1. {$text}\n" : "- {$text}\n",
            'ul', 'ol' => "\n\n{$inner}\n",
            'a' => self::link($node, $text),
            'img' => self::image($node),
            'script', 'style' => '',
            default => $inner,
        };
    }

    private static function link(DOMElement $node, string $text): string
    {
        $href = trim($node->getAttribute('href'));

        return preg_match('#^(https?:|mailto:|/)#i', $href) === 1 ? "[{$text}]({$href})" : $text;
    }

    private static function image(DOMElement $node): string
    {
        $source = trim($node->getAttribute('src'));

        return preg_match('#^(https?:|/)#i', $source) === 1
            ? '!['.$node->getAttribute('alt').']('.$source.')'
            : '';
    }
}
