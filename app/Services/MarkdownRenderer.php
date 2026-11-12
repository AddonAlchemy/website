<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;

final class MarkdownRenderer
{
    public function toHtml(?string $markdown): HtmlString
    {
        if ($markdown === null || trim($markdown) === '') {
            return new HtmlString('');
        }

        return new HtmlString(Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'external_link' => [
                'internal_hosts' => [parse_url((string) config('app.url'), PHP_URL_HOST)],
                'open_in_new_window' => true,
                'nofollow' => 'external',
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ], [new ExternalLinkExtension()]));
    }

    public function excerpt(?string $markdown, int $limit = 160): string
    {
        return Str::limit(trim(strip_tags($this->toHtml($markdown)->toHtml())), $limit);
    }
}
