<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\SettlementStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateSettlementRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(SettlementStatus::class)],
        ];
    }
}
