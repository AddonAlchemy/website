<?php

declare(strict_types=1);

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateVendorProfileRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'tagline' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:3000'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
