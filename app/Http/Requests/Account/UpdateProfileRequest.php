<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'street' => ['nullable', 'string', 'max:160'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:80'],
            'country' => ['required', 'string', 'size:2', 'alpha'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['country' => strtoupper((string) $this->input('country', 'DE'))]);
    }
}
