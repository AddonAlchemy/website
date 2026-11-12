<?php

declare(strict_types=1);

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreApiTokenRequest extends FormRequest
{
    public const ABILITIES = [
        'licenses:read' => 'Lizenzen lesen',
        'downloads:read' => 'Download-Links erzeugen',
    ];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in(array_keys(self::ABILITIES))],
        ];
    }
}
