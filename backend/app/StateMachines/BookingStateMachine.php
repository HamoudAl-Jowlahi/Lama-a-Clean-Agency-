<?php

namespace App\StateMachines;

use App\Enums\ActorType;
use App\Enums\BookingStatus;

/**
 * pending ─► confirmed ─► assigned ─► on_the_way ─► in_progress ─► completed
 *   └► rejected           ▲    │ (رفض العاملة / سحب الإدارة يعيده إلى confirmed)
 * الإلغاء: العميل حتى assigned (وفق السياسة في BookingService)، والإدارة قبل completed.
 */
class BookingStateMachine extends StateMachine
{
    protected function statusEnum(): string
    {
        return BookingStatus::class;
    }

    protected function transitions(): array
    {
        $admin = ActorType::Admin;
        $customer = ActorType::Customer;
        $worker = ActorType::Worker;

        return [
            'pending' => [
                'confirmed' => [$admin],
                'rejected' => [$admin],
                'cancelled' => [$customer, $admin],
            ],
            'confirmed' => [
                'assigned' => [$admin],
                'cancelled' => [$customer, $admin],
            ],
            'assigned' => [
                'confirmed' => [$worker, $admin],
                'on_the_way' => [$worker],
                'cancelled' => [$customer, $admin],
            ],
            'on_the_way' => [
                'in_progress' => [$worker],
                'cancelled' => [$admin],
            ],
            'in_progress' => [
                'completed' => [$worker, $admin],
                'cancelled' => [$admin],
            ],
        ];
    }
}
