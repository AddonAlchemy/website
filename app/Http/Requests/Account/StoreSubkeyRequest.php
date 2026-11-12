<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSubkeyRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:60'],
            'plugins' => ['required', 'array', 'min:1'],
            'plugins.*' => ['integer'],
        ];
    }
}
