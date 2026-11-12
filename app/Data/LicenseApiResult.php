<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Http\JsonResponse;

final readonly class LicenseApiResult
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        public int $status,
        public array $body,
    ) {
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public static function ok(array $body): self
    {
        return new self(200, $body);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public static function invalid(int $status, string $reason, ?string $message = null, array $extra = []): self
    {
        return new self($status, array_filter([
            'status' => 'invalid',
            'reason' => $reason,
            'message' => $message,
        ]) + $extra);
    }

    public function toResponse(): JsonResponse
    {
        return response()->json($this->body, $this->status, [
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
