<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class SaveCategoryRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'icon' => ['required', 'string', 'max:40', 'regex:/^[a-z0-9\-]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'between:0,1000'],
        ];
    }
}
