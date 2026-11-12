<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\MarkdownRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;

final class Markdown extends Component
{
    public HtmlString $html;

    public function __construct(?string $content, MarkdownRenderer $renderer)
    {
        $this->html = $renderer->toHtml($content);
    }

    public function render(): View
    {
        return view('components.markdown');
    }
}
