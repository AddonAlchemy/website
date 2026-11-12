<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\License;
use App\Models\LicenseCode;
use App\Models\LicenseSubkey;
use App\Models\Order;
use App\Models\Plugin;
use App\Models\PluginVersion;
use App\Models\Settlement;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;

final class KeyGenerator
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function make(string $prefix, int $groups, int $length): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $parts = [];

        for ($group = 0; $group < $groups; $group++) {
            $part = '';

            for ($i = 0; $i < $length; $i++) {
                $part .= self::ALPHABET[random_int(0, $max)];
            }

            $parts[] = $part;
        }

        return $prefix.'-'.implode('-', $parts);
    }

    public function licenseKey(): string
    {
        return $this->unique(License::class, 'key', 'AALC', 4, 4);
    }

    public function subkey(): string
    {
        return $this->unique(LicenseSubkey::class, 'key', 'SUB', 3, 4);
    }

    public function licenseCode(): string
    {
        return $this->unique(LicenseCode::class, 'code', 'LIC', 3, 4);
    }

    public function instanceId(): string
    {
        return $this->make('INST', 2, 5);
    }

    public function pluginId(): string
    {
        return $this->unique(Plugin::class, 'public_id', 'PRD', 2, 4);
    }

    public function versionId(): string
    {
        return $this->unique(PluginVersion::class, 'public_id', 'VER', 2, 4);
    }

    public function orderNumber(): string
    {
        return $this->unique(Order::class, 'number', 'ORD', 2, 4);
    }

    public function ticketNumber(): string
    {
        return $this->unique(Ticket::class, 'number', 'TCK', 2, 4);
    }

    public function settlementNumber(): string
    {
        return $this->unique(Settlement::class, 'number', 'TXN', 3, 4);
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function unique(string $model, string $column, string $prefix, int $groups, int $length): string
    {
        do {
            $key = $this->make($prefix, $groups, $length);
        } while ($model::query()->where($column, $key)->exists());

        return $key;
    }
}
