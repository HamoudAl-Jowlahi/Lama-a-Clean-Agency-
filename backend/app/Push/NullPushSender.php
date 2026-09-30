<?php

namespace App\Push;

use App\Contracts\PushSender;

/** لا يرسل شيئاً (لإيقاف الـ Push مؤقتاً). */
class NullPushSender implements PushSender
{
    public function send(array $tokens, string $title, string $body, array $data = []): array
    {
        return [];
    }
}
