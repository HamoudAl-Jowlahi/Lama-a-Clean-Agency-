<?php

namespace App\StateMachines;

use App\Enums\ActorType;
use App\Enums\ContractStatus;

/**
 * pending ─► confirmed ─► assigned ─► active ─► completed
 *   └► rejected                          └► terminated (إنهاء مبكر)
 * الإلغاء قبل البدء فقط. التفعيل والانتهاء تلقائيان (System) أو يدويان من الإدارة.
 * استبدال العاملة لا يمر من هنا — العقد يبقى active.
 */
class ContractStateMachine extends StateMachine
{
    protected function statusEnum(): string
    {
        return ContractStatus::class;
    }

    protected function transitions(): array
    {
        $admin = ActorType::Admin;
        $customer = ActorType::Customer;
        $system = ActorType::System;

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
                'active' => [$system, $admin],
                'confirmed' => [$admin],
                'cancelled' => [$customer, $admin],
            ],
            'active' => [
                'completed' => [$system, $admin],
                'terminated' => [$admin],
            ],
        ];
    }
}
