<?php

declare(strict_types=1);

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

final class StoreScreenshotRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:'.config('marketplace.media.max_screenshots')],
            'images.*' => ['image', 'mimes:png,jpg,jpeg,webp', 'max:'.config('marketplace.media.max_image_kb')],
            'caption' => ['nullable', 'string', 'max:120'],
        ];
    }
}
