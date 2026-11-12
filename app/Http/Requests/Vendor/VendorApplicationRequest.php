<?php

declare(strict_types=1);

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VendorApplicationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80', Rule::unique('vendors', 'name')],
            'tagline' => ['nullable', 'string', 'max:160'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'terms' => ['accepted'],
        ];
    }
}
