<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use App\Data\CatalogFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CatalogRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:120'],
            'price' => ['nullable', Rule::in(['free', 'paid'])],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'downloads' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', Rule::in(array_keys(CatalogFilters::SORTS))],
        ];
    }

    public function filters(): CatalogFilters
    {
        return CatalogFilters::fromArray(array_filter(
            $this->validated(),
            static fn (mixed $value): bool => $value !== null && $value !== '',
        ));
    }
}
