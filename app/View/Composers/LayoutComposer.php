<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Enums\AnnouncementType;
use App\Models\Announcement;
use App\Services\Cart;
use App\Services\Settings;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class LayoutComposer
{
    public function __construct(
        private readonly Guard $auth,
        private readonly Settings $settings,
        private readonly Cart $cart,
    ) {
    }

    public function compose(View $view): void
    {
        $user = $this->auth->user();

        $announcements = Announcement::query()
            ->visibleTo($user)
            ->latest()
            ->limit(15)
            ->get();

        $readIds = $user !== null && $announcements->isNotEmpty()
            ? DB::table('announcement_reads')
                ->where('user_id', $user->id)
                ->whereIn('announcement_id', $announcements->modelKeys())
                ->pluck('announcement_id')
                ->all()
            : [];

        $view->with([
            'settings' => $this->settings,
            'cartCount' => $this->cart->count(),
            'announcements' => $announcements,
            'unreadAnnouncements' => $user !== null
                ? $announcements->reject(static fn (Announcement $a): bool => in_array($a->id, $readIds, true))->count()
                : 0,
            'banner' => $announcements->first(static fn (Announcement $a): bool => $a->type->isBanner()),
            'bannerTypes' => AnnouncementType::bannerValues(),
            'notifications' => $user?->notifications()->limit(15)->get() ?? new Collection(),
            'unreadNotifications' => $user?->unreadNotifications()->count() ?? 0,
        ]);
    }
}
