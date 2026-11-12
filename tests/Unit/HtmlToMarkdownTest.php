<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\HtmlToMarkdown;
use PHPUnit\Framework\TestCase;

final class HtmlToMarkdownTest extends TestCase
{
    public function test_it_converts_rich_text_to_markdown(): void
    {
        $markdown = HtmlToMarkdown::convert('<h2>Features</h2><p>Sehr <strong>schnell</strong> und <em>leicht</em>.</p><ul><li>Eins</li><li>Zwei</li></ul><p><a href="https://example.com">Doku</a></p>');

        $this->assertStringContainsString('## Features', $markdown);
        $this->assertStringContainsString('Sehr **schnell** und *leicht*.', $markdown);
        $this->assertStringContainsString("- Eins\n- Zwei", $markdown);
        $this->assertStringContainsString('[Doku](https://example.com)', $markdown);
    }

    public function test_it_drops_scripts_and_unsafe_links(): void
    {
        $markdown = HtmlToMarkdown::convert('<script>alert(1)</script><a href="javascript:alert(1)">Klick</a><img src="data:image/png;base64,AAA">');

        $this->assertSame('Klick', $markdown);
    }
}
