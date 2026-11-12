<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAnnouncementRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'body' => ['required', 'string', 'max:500'],
            'type' => ['required', Rule::enum(AnnouncementType::class)],
            'audience' => ['required', Rule::enum(AnnouncementAudience::class)],
        ];
    }
}
