<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\PaymentMethod;
use App\Models\Setting;
use Illuminate\Contracts\Cache\Repository as Cache;

final class Settings
{
    private const CACHE_KEY = 'marketplace.settings';

    /** @var array<string, mixed>|null */
    private ?array $resolved = null;

    public function __construct(private readonly Cache $cache)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->resolved ??= array_replace(
            config('marketplace.settings'),
            $this->cache->rememberForever(
                self::CACHE_KEY,
                static fn (): array => Setting::query()->get()->pluck('value', 'key')->all(),
            ),
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function put(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->cache->forget(self::CACHE_KEY);
        $this->resolved = null;
    }

    /**
     * @return list<PaymentMethod>
     */
    public function paymentMethods(): array
    {
        return array_map(PaymentMethod::fromArray(...), array_values($this->get('payment_methods', [])));
    }

    public function paymentMethod(?string $id): PaymentMethod
    {
        $methods = $this->paymentMethods();

        foreach ($methods as $method) {
            if ($method->id === $id) {
                return $method;
            }
        }

        return $methods[0] ?? new PaymentMethod('card', 'Kreditkarte', 1.5, 25);
    }

    public function maxIps(): int
    {
        return max(1, (int) $this->get('max_ips'));
    }

    public function subkeyMaxPlugins(): int
    {
        return max(1, (int) $this->get('subkey_max_plugins'));
    }

    public function subkeyLockDays(): int
    {
        return max(0, (int) $this->get('subkey_lock_days'));
    }

    public function minPriceCents(): int
    {
        return max(0, (int) $this->get('min_price_cents'));
    }

    public function platformFeePercent(): float
    {
        return max(0.0, min(100.0, (float) $this->get('platform_fee_percent')));
    }

    public function accentColor(): string
    {
        $color = (string) $this->get('accent_color');

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1 ? $color : '#88C040';
    }

    public function accentHoverColor(): string
    {
        [$r, $g, $b] = sscanf($this->accentColor(), '#%02x%02x%02x');
        $lighten = static fn (int $channel): int => (int) min(255, round($channel + (255 - $channel) * 0.14));

        return sprintf('#%02x%02x%02x', $lighten($r), $lighten($g), $lighten($b));
    }
}
