<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * إشعار لمستخدم التطبيق (عميل أو موظف): يُحفظ دائماً في قائمة الإشعارات،
 * ويُرسل Push إن كان المستخدم مفعّلاً لهذه الفئة (orders | complaints).
 *
 * النص من lang/{locale}/notifications.php بلغة المستخدم (User::preferredLocale).
 * data للتطبيق: event + subject {type, id, number} لفتح الشاشة المناسبة.
 */
class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $audience  customer | staff
     * @param  array{type: string, id: int, number: ?string}  $subject
     */
    public function __construct(
        public string $event,
        public string $audience,
        public array $params,
        public array $subject,
        public string $category,
    ) {
        $this->afterCommit();
    }

    public function via(User $notifiable): array
    {
        return $notifiable->wantsPush($this->category) ? ['database', PushChannel::class] : ['database'];
    }

    public function title(): string
    {
        return __("notifications.{$this->event}.{$this->audience}.title", $this->params);
    }

    public function body(): string
    {
        return __("notifications.{$this->event}.{$this->audience}.body", $this->params);
    }

    public function toArray(User $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title(),
            'body' => $this->body(),
            'subject' => $this->subject,
        ];
    }

    /** @return array{title: string, body: string, data: array<string, string>} */
    public function toPush(User $notifiable): array
    {
        return [
            'title' => $this->title(),
            'body' => $this->body(),
            'data' => [
                'event' => $this->event,
                'subject_type' => $this->subject['type'],
                'subject_id' => (string) $this->subject['id'],
                'subject_number' => (string) ($this->subject['number'] ?? ''),
            ],
        ];
    }
}
