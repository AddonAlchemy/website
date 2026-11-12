<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plugin;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\DB;

final class FavoriteRegistry
{
    /** @var array<int, true>|null */
    private ?array $pluginIds = null;

    public function __construct(private readonly Guard $auth)
    {
    }

    public function contains(Plugin $plugin): bool
    {
        return isset($this->pluginIds()[$plugin->id]);
    }

    public function flush(): void
    {
        $this->pluginIds = null;
    }

    /**
     * @return array<int, true>
     */
    private function pluginIds(): array
    {
        if ($this->pluginIds !== null) {
            return $this->pluginIds;
        }

        $userId = $this->auth->id();

        return $this->pluginIds = $userId === null
            ? []
            : array_fill_keys(DB::table('favorites')->where('user_id', $userId)->pluck('plugin_id')->all(), true);
    }
}
