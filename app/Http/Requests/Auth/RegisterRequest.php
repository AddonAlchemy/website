<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_type' => ['required', Rule::in(['customer', 'vendor'])],
            'name' => ['nullable', 'string', 'max:60'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'studio' => ['nullable', 'required_if:account_type,vendor', 'string', 'min:2', 'max:80', Rule::unique('vendors', 'name')],
            'terms' => ['accepted'],
        ];
    }
}
