<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use Illuminate\View\ComponentAttributeBag;

final class Icon extends Component
{
    private const FALLBACK = 'package';

    /** @var array<string, string> */
    private static array $cache = [];

    public function __construct(public string $name)
    {
    }

    public function render(): View
    {
        return view('components.icon');
    }

    public function svg(ComponentAttributeBag $attributes): HtmlString
    {
        $markup = self::$cache[$this->name] ??= $this->load($this->name) ?? $this->load(self::FALLBACK) ?? '<svg></svg>';

        return new HtmlString(preg_replace(
            '/^<svg/',
            '<svg '.$attributes->merge(['class' => 'icon', 'aria-hidden' => 'true'])->toHtml(),
            $markup,
            1,
        ));
    }

    private function load(string $name): ?string
    {
        if (preg_match('/^[a-z0-9\-]+$/', $name) !== 1) {
            return null;
        }

        $path = resource_path('svg/'.$name.'.svg');

        return is_file($path) ? trim((string) file_get_contents($path)) : null;
    }
}
