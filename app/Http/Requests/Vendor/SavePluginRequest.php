<?php

declare(strict_types=1);

namespace App\Http\Requests\Vendor;

use App\Enums\LicenseModel;
use App\Services\Settings;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SavePluginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'summary' => ['required', 'string', 'min:10', 'max:255'],
            'description' => ['nullable', 'string', 'max:30000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'price' => ['required', 'numeric', 'min:0', 'max:100000', $this->minimumPriceRule()],
            'license_model' => ['required', Rule::enum(LicenseModel::class)],
            'tags' => ['nullable', 'string', 'max:200'],
            'icon' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:'.config('marketplace.media.max_image_kb'),
                'dimensions:max_width=2048,max_height=2048',
            ],
        ];
    }

    /**
     * @return array{name: string, summary: string, description: ?string, category_id: int, price_cents: int, license_model: string, tags: list<string>}
     */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->trim()->value(),
            'summary' => $this->string('summary')->trim()->value(),
            'description' => $this->input('description'),
            'category_id' => $this->integer('category_id'),
            'price_cents' => (int) round($this->float('price') * 100),
            'license_model' => $this->string('license_model')->value(),
            'tags' => array_values(array_filter(array_map('trim', explode(',', (string) $this->input('tags'))))),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['price' => str_replace(',', '.', (string) $this->input('price', '0'))]);
    }

    private function minimumPriceRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            $cents = (int) round(((float) $value) * 100);
            $minimum = app(Settings::class)->minPriceCents();

            if ($cents > 0 && $cents < $minimum) {
                $fail('Der Preis muss 0 (gratis) oder mindestens '.money($minimum).' betragen.');
            }
        };
    }
}
