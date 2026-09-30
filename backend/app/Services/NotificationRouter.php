<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Enums\AssignmentStatus;
use App\Events\DomainEvent;
use App\Events\StatusChanged;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\ChangeRequests\ChangeRequestResource;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Resources\Contracts\ContractResource;
use App\Models\AdminUser;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\ContractChangeRequest;
use App\Models\Payment;
use App\Models\Team;
use App\Models\User;
use App\Models\Worker;
use App\Notifications\AppNotification;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Notifications\Notification as PanelNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * يحوّل أحداث النظام إلى إشعارات:
 *  - العميل والموظفون: AppNotification (قائمة الإشعارات + Push)
 *  - الإدارة: إشعار داخل لوحة الإدارة مع رابط للسجل
 *
 * من يستلم ماذا: مصفوفة agency.notifications (قابلة للتعديل من الإعدادات).
 * لا يُشعَر من قام بالفعل نفسه (العميل الذي ألغى، الإدارة التي أكدت...).
 */
class NotificationRouter
{
    private const STAFF_BUSY_STATUSES = ['assigned', 'on_the_way', 'in_progress'];

    public function handleStatusChanged(StatusChanged $event): void
    {
        $subject = $event->subject;
        $from = $event->from->value;
        $to = $event->to->value;
        $context = ['actor' => $event->actor];

        if ($subject instanceof Booking) {
            if ($from === 'assigned' && $to === 'confirmed') {
                // رفض القائد → الإدارة تعيد الإسناد · سحب الإدارة → الفريق يعلم
                $event->actor === ActorType::Worker
                    ? $this->dispatch('booking.declined', $subject, $context + ['team' => $this->lastTeam($subject)])
                    : $this->dispatch('booking.unassigned', $subject, $context + ['team' => $this->lastTeam($subject)]);

                return;
            }
            if ($to === 'assigned' || ($to === 'cancelled' && in_array($from, self::STAFF_BUSY_STATUSES, true))) {
                $context['team'] = $this->lastTeam($subject);
            }
            $this->dispatch("booking.{$to}", $subject, $context);

            return;
        }

        if ($subject instanceof Contract) {
            if ($from === 'assigned' && $to === 'confirmed') {
                return; // سحب الخادمة قبل البدء — شأن داخلي
            }
            $context['worker'] = $subject->assignments()->with('worker.user')->reorder('id', 'desc')->first()?->worker;
            $this->dispatch("contract.{$to}", $subject, $context);
        }
    }

    public function handleDomainEvent(DomainEvent $event): void
    {
        $this->dispatch($event->name, $event->subject, $event->context);
    }

    public function dispatch(string $event, Model $subject, array $context = []): void
    {
        $audiences = $this->matrix()[$event] ?? null;
        if (! $audiences) {
            return;
        }

        $actor = $context['actor'] ?? null;
        [$ref, $params] = $this->describe($subject, $context);
        $category = str_starts_with($event, 'complaint.') ? 'complaints' : 'orders';

        if (($audiences['customer'] ?? false) && $actor !== ActorType::Customer && ($customer = $this->customerUser($subject))) {
            Notification::send($customer, new AppNotification($event, 'customer', $params, $ref, $category));
        }

        if (($audiences['staff'] ?? false) && $actor !== ActorType::Worker) {
            $staff = $this->staffUsers($subject, $context);
            if ($staff->isNotEmpty()) {
                Notification::send($staff, new AppNotification($event, 'staff', $params, $ref, $category));
            }
        }

        if (($audiences['admins'] ?? false) && $actor !== ActorType::Admin) {
            $admins = $this->admins($event);
            if ($admins->isNotEmpty()) {
                PanelNotification::make()
                    ->title(__("notifications.{$event}.admins.title", $params))
                    ->body(__("notifications.{$event}.admins.body", $params))
                    ->icon('heroicon-o-bell-alert')
                    ->actions([Action::make('view')->label(__('notifications.view'))->url($this->adminUrl($subject))->markAsRead()])
                    ->sendToDatabase($admins);
            }
        }
    }

    /** @return array<string, array{customer: bool, staff: bool, admins: bool}> */
    public function matrix(): array
    {
        // القيم المحفوظة من الإعدادات فوق الافتراضية (الأحداث الجديدة تأخذ الافتراضي)
        return array_replace_recursive(config('agency.notifications'), (array) Settings::get('notifications'));
    }

    // ------------------------------------------------------------ details

    /** @return array{0: array{type: string, id: int, number: ?string}, 1: array<string, string>} */
    private function describe(Model $subject, array $context): array
    {
        $params = [];

        if ($subject instanceof Booking) {
            $ref = ['type' => 'booking', 'id' => $subject->id, 'number' => $subject->booking_number];
            $params += [
                'number' => $subject->booking_number,
                'date' => $subject->scheduled_date->format('Y-m-d'),
                'time' => substr((string) $subject->scheduled_time, 0, 5),
                'amount' => number_format((float) $subject->total, 2).' '.$subject->currency,
                'team' => ($context['team'] ?? null)?->name ?? '',
                'reason' => (string) $subject->cancel_reason,
            ];
        } elseif ($subject instanceof Contract) {
            $ref = ['type' => 'contract', 'id' => $subject->id, 'number' => $subject->contract_number];
            $params += $this->contractParams($subject, $context);
        } elseif ($subject instanceof ContractChangeRequest) {
            $contract = Contract::findOrFail($subject->contract_id);
            $ref = ['type' => 'contract', 'id' => $contract->id, 'number' => $contract->contract_number];
            $params += $this->contractParams($contract, $context) + [
                'request' => (string) $subject->request_number,
                'request_type' => $subject->type->label(),
                'reason' => __('api.change_request_reasons.'.$subject->reason_type),
                'response' => (string) $subject->admin_response,
            ];
        } elseif ($subject instanceof Complaint) {
            $ref = ['type' => 'complaint', 'id' => $subject->id, 'number' => $subject->complaint_number];
            $params += [
                'number' => (string) $subject->complaint_number,
                'type' => __('api.complaint_types.'.$subject->type),
                'status' => (string) $subject->status?->label(),
            ];
        } elseif ($subject instanceof Payment) {
            $owner = $subject->booking_id ? Booking::findOrFail($subject->booking_id) : Contract::findOrFail($subject->contract_id);
            [$ref] = $this->describe($owner, $context);
            $params += [
                'number' => (string) $ref['number'],
                'amount' => number_format((float) $subject->amount, 2).' '.$subject->currency,
                'date' => $subject->due_date->format('Y-m-d'),
            ];
        } else {
            $ref = ['type' => $subject->getMorphClass(), 'id' => $subject->getKey(), 'number' => null];
        }

        return [$ref, array_map('strval', $params)];
    }

    private function contractParams(Contract $contract, array $context): array
    {
        $worker = $context['worker'] ?? null;

        return [
            'number' => $contract->contract_number,
            'date' => $contract->start_date->format('Y-m-d'),
            'end_date' => $contract->end_date->format('Y-m-d'),
            'worker' => $worker instanceof Worker ? strtok($worker->user->name, ' ') : '',
            'start_on' => (string) ($context['start_on'] ?? ''),
            'reason' => (string) ($contract->termination_reason ?? $contract->cancel_reason),
        ];
    }

    private function customerUser(Model $subject): ?User
    {
        $customerId = match (true) {
            $subject instanceof Payment => $subject->booking_id
                ? Booking::whereKey($subject->booking_id)->value('customer_id')
                : Contract::whereKey($subject->contract_id)->value('customer_id'),
            default => $subject->customer_id ?? null,
        };

        return $customerId ? User::whereHas('customer', fn ($q) => $q->whereKey($customerId))->first() : null;
    }

    /** @return Collection<int, User> */
    private function staffUsers(Model $subject, array $context): Collection
    {
        if ($subject instanceof Booking) {
            $team = $context['team'] ?? $this->lastTeam($subject, activeOnly: true);

            return $team ? User::whereHas('worker.teams', fn ($q) => $q->whereKey($team->id))->get() : collect();
        }

        $worker = $context['worker'] ?? null;

        return $worker instanceof Worker ? collect([$worker->user]) : collect();
    }

    private function lastTeam(Booking $booking, bool $activeOnly = false): ?Team
    {
        return $booking->assignments()
            ->when($activeOnly, fn ($q) => $q->whereIn('status', [AssignmentStatus::Pending, AssignmentStatus::Accepted]))
            ->with('team')
            ->reorder('id', 'desc')
            ->first()?->team;
    }

    /** @return Collection<int, AdminUser> */
    private function admins(string $event): Collection
    {
        $ability = match (true) {
            str_starts_with($event, 'complaint.') => 'complaints.manage',
            str_starts_with($event, 'booking.') => 'bookings.manage',
            default => 'contracts.manage',
        };

        return AdminUser::where('is_active', true)->get()->filter(fn (AdminUser $a) => $a->hasAbility($ability))->values();
    }

    private function adminUrl(Model $subject): string
    {
        return match (true) {
            $subject instanceof Booking => BookingResource::getUrl('view', ['record' => $subject->id], panel: 'admin'),
            $subject instanceof Contract => ContractResource::getUrl('view', ['record' => $subject->id], panel: 'admin'),
            $subject instanceof ContractChangeRequest => ChangeRequestResource::getUrl('view', ['record' => $subject->id], panel: 'admin'),
            $subject instanceof Complaint => ComplaintResource::getUrl('view', ['record' => $subject->id], panel: 'admin'),
            $subject instanceof Payment => $subject->booking_id
                ? BookingResource::getUrl('view', ['record' => $subject->booking_id], panel: 'admin')
                : ContractResource::getUrl('view', ['record' => $subject->contract_id], panel: 'admin'),
            default => url('/admin'),
        };
    }
}
