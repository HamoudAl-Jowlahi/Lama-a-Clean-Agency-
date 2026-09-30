<?php

namespace App\Push;

use App\Contracts\PushSender;
use Illuminate\Support\Facades\Log;

/** للتطوير: يكتب الإشعار في storage/logs بدل إرساله. */
class LogPushSender implements PushSender
{
    public function send(array $tokens, string $title, string $body, array $data = []): array
    {
        Log::info('[push] '.$title, ['body' => $body, 'data' => $data, 'devices' => count($tokens)]);

        return [];
    }
}
