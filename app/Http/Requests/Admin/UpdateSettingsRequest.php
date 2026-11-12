<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class UpdateSettingsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'brand_first' => ['required', 'string', 'max:30'],
            'brand_second' => ['required', 'string', 'max:30'],
            'tagline' => ['nullable', 'string', 'max:300'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'currency' => ['required', 'string', 'max:5'],
            'maintenance' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:300'],
            'max_ips' => ['required', 'integer', 'between:1,20'],
            'subkey_max_plugins' => ['required', 'integer', 'between:1,20'],
            'subkey_lock_days' => ['required', 'integer', 'between:0,365'],
            'min_price' => ['required', 'numeric', 'min:0', 'max:10000'],
            'platform_fee_percent' => ['required', 'numeric', 'between:0,100'],
            'payment_methods' => ['required', 'array', 'min:1', 'max:10'],
            'payment_methods.*.label' => ['nullable', 'string', 'max:40'],
            'payment_methods.*.percent' => ['nullable', 'numeric', 'between:0,100'],
            'payment_methods.*.fixed' => ['nullable', 'numeric', 'between:0,1000'],
            'company_name' => ['nullable', 'string', 'max:120'],
            'company_address' => ['nullable', 'string', 'max:300'],
            'company_email' => ['nullable', 'email', 'max:120'],
            'cookie_enabled' => ['nullable', 'boolean'],
            'cookie_text' => ['nullable', 'string', 'max:600'],
            'legal_imprint' => ['nullable', 'string', 'max:30000'],
            'legal_privacy' => ['nullable', 'string', 'max:30000'],
            'legal_terms' => ['nullable', 'string', 'max:30000'],
            'legal_vendor_terms' => ['nullable', 'string', 'max:30000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $methods = collect($this->input('payment_methods', []))
            ->filter(static fn (array $method): bool => filled($method['label'] ?? null))
            ->values()
            ->map(static fn (array $method, int $index): array => [
                'id' => Str::slug($method['label'], '') ?: 'method'.$index,
                'label' => $method['label'],
                'percent' => (float) ($method['percent'] ?? 0),
                'fixed_cents' => (int) round(((float) ($method['fixed'] ?? 0)) * 100),
            ])
            ->all();

        $values = $this->safe()->except(['min_price', 'payment_methods']);

        return [
            ...$values,
            'maintenance' => $this->boolean('maintenance'),
            'cookie_enabled' => $this->boolean('cookie_enabled'),
            'max_ips' => $this->integer('max_ips'),
            'subkey_max_plugins' => $this->integer('subkey_max_plugins'),
            'subkey_lock_days' => $this->integer('subkey_lock_days'),
            'platform_fee_percent' => $this->float('platform_fee_percent'),
            'min_price_cents' => (int) round($this->float('min_price') * 100),
            ...($methods === [] ? [] : ['payment_methods' => $methods]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $decimal = static fn (mixed $value): string => str_replace(',', '.', (string) $value);

        $this->merge([
            'min_price' => $decimal($this->input('min_price')),
            'platform_fee_percent' => $decimal($this->input('platform_fee_percent')),
            'payment_methods' => array_map(static fn (array $method): array => [
                'label' => $method['label'] ?? null,
                'percent' => $decimal($method['percent'] ?? 0),
                'fixed' => $decimal($method['fixed'] ?? 0),
            ], array_filter((array) $this->input('payment_methods', []), 'is_array')),
        ]);
    }
}
