<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Enums\VendorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTicketRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:3000'],
            'vendor_id' => ['nullable', 'integer', Rule::exists('vendors', 'id')->where('status', VendorStatus::Approved->value)],
            'plugin_id' => ['nullable', 'integer', Rule::exists('plugins', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->vendor !== null) {
            $this->merge(['vendor_id' => null]);
        }
    }
}
