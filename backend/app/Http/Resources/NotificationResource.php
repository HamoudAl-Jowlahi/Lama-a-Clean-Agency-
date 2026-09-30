<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\FormatsValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * عنصر في قائمة الإشعارات. subject يوجّه التطبيق للشاشة المناسبة:
 * booking → تفاصيل الزيارة · contract → العقد · complaint → الشكوى.
 *
 * @mixin \Illuminate\Notifications\DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    use FormatsValues;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->data['event'] ?? null,
            'title' => $this->data['title'] ?? null,
            'body' => $this->data['body'] ?? null,
            'subject' => $this->data['subject'] ?? null,
            'read' => $this->read_at !== null,
            'created_at' => $this->datetime($this->created_at),
        ];
    }
}
