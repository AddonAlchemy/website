<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SaleScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveSaleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'percent' => ['required', 'numeric', 'gt:0', 'max:100'],
            'scope' => ['required', Rule::enum(SaleScope::class)],
            'vendor_id' => ['nullable', 'required_if:scope,vendor', 'integer', Rule::exists('vendors', 'id')],
            'bearer' => ['required', Rule::in(['shop', 'vendor', 'split'])],
            'vendor_share_percent' => ['nullable', 'integer', 'between:0,100'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $scope = SaleScope::from($this->string('scope')->value());

        return [
            'name' => $this->string('name')->value(),
            'percent' => $this->float('percent'),
            'scope' => $scope,
            'vendor_id' => $scope === SaleScope::Vendor ? $this->integer('vendor_id') : null,
            'vendor_share_percent' => match ($this->input('bearer')) {
                'shop' => 0,
                'vendor' => 100,
                default => $this->integer('vendor_share_percent', 50),
            },
            'starts_at' => $this->date('starts_at'),
            'ends_at' => $this->date('ends_at'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['percent' => str_replace(',', '.', (string) $this->input('percent'))]);
    }
}
