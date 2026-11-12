<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\VendorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVendorStatusRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(VendorStatus::class)],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
