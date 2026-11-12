<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;

final class ActivityNotification extends Notification
{
    public function __construct(
        private readonly string $title,
        private readonly string $body,
        private readonly string $icon = 'bell',
        private readonly ?string $url = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, icon: string, url: ?string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'url' => $this->url,
        ];
    }
}
