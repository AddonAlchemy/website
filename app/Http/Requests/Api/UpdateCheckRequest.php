<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCheckRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'max:64'],
            'version' => ['nullable', 'string', 'max:40'],
            'key' => ['nullable', 'string', 'max:64'],
            'platform' => ['nullable', 'string', 'max:40'],
        ];
    }
}
