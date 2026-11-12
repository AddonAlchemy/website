<?php

declare(strict_types=1);

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

final class StoreVersionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'version' => [
                'required',
                'string',
                'max:40',
                'regex:/^\d+(\.\d+){0,3}([\-+][0-9A-Za-z.\-]+)?$/',
                Rule::unique('plugin_versions', 'version')->where('plugin_id', $this->route('plugin')->id),
            ],
            'changelog' => ['nullable', 'string', 'max:10000'],
            'game_versions' => ['nullable', 'string', 'max:300'],
            'platforms' => ['nullable', 'array', 'max:20'],
            'platforms.*' => ['integer', Rule::exists('platforms', 'id')],
            'archive' => [
                'required',
                File::types(config('marketplace.releases.extensions'))->max(config('marketplace.releases.max_size_kb')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'version.regex' => 'Bitte eine Versionsnummer im Format 1.2.3 angeben.',
            'version.unique' => 'Diese Versionsnummer existiert bereits für dieses Plugin.',
        ];
    }

    /**
     * @return array{version: string, changelog: ?string, game_versions: list<string>, platforms: list<int>}
     */
    public function payload(): array
    {
        $gameVersions = array_filter(array_map('trim', explode(',', (string) $this->input('game_versions'))));

        return [
            'version' => $this->string('version')->value(),
            'changelog' => $this->input('changelog'),
            'game_versions' => array_slice(array_map(static fn (string $v): string => mb_substr($v, 0, 20), array_values($gameVersions)), 0, 20),
            'platforms' => array_map('intval', $this->input('platforms', [])),
        ];
    }
}
