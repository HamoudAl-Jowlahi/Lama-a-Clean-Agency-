<?php

namespace App\Notifications\Channels;

use App\Contracts\PushSender;
use App\Models\DeviceToken;
use App\Models\User;
use App\Notifications\AppNotification;

/** قناة Push: ترسل لكل أجهزة المستخدم عبر PushSender، وتحذف الرموز غير الصالحة. */
class PushChannel
{
    public function __construct(private PushSender $sender) {}

    public function send(User $notifiable, AppNotification $notification): void
    {
        $tokens = $notifiable->deviceTokens()->pluck('token')->all();
        if ($tokens === []) {
            return;
        }

        $message = $notification->toPush($notifiable);
        $invalid = $this->sender->send($tokens, $message['title'], $message['body'], $message['data']);

        if ($invalid !== []) {
            DeviceToken::whereIn('token', $invalid)->delete();
        }
    }
}
