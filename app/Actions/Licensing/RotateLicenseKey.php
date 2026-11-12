<?php

declare(strict_types=1);

namespace App\Actions\Licensing;

use App\Models\License;
use App\Services\KeyGenerator;
use Illuminate\Support\Facades\DB;

final class RotateLicenseKey
{
    public function __construct(private readonly KeyGenerator $keys)
    {
    }

    public function handle(License $license): License
    {
        return DB::transaction(function () use ($license): License {
            $license->update(['key' => $this->keys->licenseKey()]);
            $license->activations()->delete();

            return $license;
        });
    }
}
