<?php

declare(strict_types=1);

use App\Services\Settings;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('money')) {
    function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.').' '.setting('currency', '€');
    }
}
