<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Enums\ContractStatus;
use App\Events\DomainEvent;
use App\Models\Contract;
use App\Models\Payment;
use App\Support\Settings;
use Carbon\CarbonImmutable;

/**
 * استحقاقات العقد النقدية (CR-1 + CR-2):
 * - payment_schedule = monthly: استحقاق لكل شهر بعد انتهائه؛ end_of_contract: استحقاق واحد.
 * - deduct_waiting_days: الأيام التي لا توجد فيها عاملة (انتظار البديلة) لا تُحتسب.
 * - الإنهاء المبكر: actual_days = بالأيام الفعلية، full_month = الفترة كاملة.
 */
class ContractBillingService
{
    public function __construct(private PricingService $pricing) {}

    /**
     * فترات الاستحقاق الكاملة حسب الجدول.
     *
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    public function periods(Contract $contract): array
    {
        $start = CarbonImmutable::parse($contract->start_date);
        $end = CarbonImmutable::parse($contract->end_date);

        if (Settings::get('contracts.payment_schedule') === 'end_of_contract') {
            return [[$start, $end]];
        }

        $periods = [];
        for ($k = 0; $k < $contract->months; $k++) {
            $pStart = $start->addMonthsNoOverflow($k);
            $pEnd = $start->addMonthsNoOverflow($k + 1)->subDay();
            $periods[] = [$pStart, $pEnd->min($end)];
        }

        return $periods;
    }

    /**
     * المبلغ المستحق عن فترة، مع احتساب الانتهاء المبكر (billUntil) وأيام الانتظار.
     */
    public function amountFor(Contract $contract, CarbonImmutable $pStart, CarbonImmutable $pEnd, ?CarbonImmutable $billUntil = null): string
    {
        $base = Settings::get('contracts.payment_schedule') === 'end_of_contract'
            ? (float) $contract->monthly_price * $contract->months
            : (float) $contract->monthly_price;

        $until = $billUntil ? $billUntil->min($pEnd) : $pEnd;
        $cutShort = $until->lt($pEnd);

        if ($cutShort && Settings::get('contracts.early_termination_calc') === 'full_month') {
            $until = $pEnd;
        }

        $fullDays = (int) $pStart->diffInDays($pEnd) + 1;
        $days = Settings::get('contracts.deduct_waiting_days')
            ? $this->coveredDays($contract, $pStart, $until)
            : (int) $pStart->diffInDays($until) + 1;

        return $this->pricing->money($base * min($days, $fullDays) / $fullDays);
    }

    /** عدد الأيام في [from, to] التي كانت فيها عاملة مسندة للعقد. */
    public function coveredDays(Contract $contract, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $contractEnd = CarbonImmutable::parse($contract->end_date);
        $covered = [];

        foreach ($contract->assignments()->get() as $a) {
            $s = CarbonImmutable::parse($a->started_on)->max($from);
            $e = ($a->ended_on ? CarbonImmutable::parse($a->ended_on) : $contractEnd)->min($to);
            for ($d = $s; $d->lte($e); $d = $d->addDay()) {
                $covered[$d->toDateString()] = true;
            }
        }

        return count($covered);
    }

    /**
     * يُنشئ الاستحقاقات للفترات المنتهية التي لم تُنشأ بعد. آمن للتكرار (يومياً).
     *
     * @return int عدد الاستحقاقات الجديدة
     */
    public function generateDuePayments(Contract $contract, ?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::parse(CarbonImmutable::now(Settings::get('timezone'))->toDateString());

        if (! in_array($contract->status, [ContractStatus::Active, ContractStatus::Completed, ContractStatus::Terminated], true)) {
            return 0;
        }

        $endedOn = $contract->status === ContractStatus::Terminated && $contract->ended_at
            ? CarbonImmutable::parse($contract->ended_at->toDateString())
            : null;

        $existing = $contract->payments()->pluck('period_start')
            ->map(fn ($d) => CarbonImmutable::parse($d)->toDateString())->all();

        $created = 0;
        foreach ($this->periods($contract) as [$pStart, $pEnd]) {
            if ($endedOn && $pStart->gt($endedOn)) {
                break; // لا استحقاق بعد تاريخ الإنهاء
            }

            $finished = $pEnd->lt($today) || ($endedOn && $endedOn->lte($pEnd) && $endedOn->lt($today->addDay()));
            if (! $finished || in_array($pStart->toDateString(), $existing, true)) {
                continue;
            }

            $amount = $this->amountFor($contract, $pStart, $pEnd, $endedOn);
            $dueDate = $endedOn ? $endedOn->min($pEnd) : $pEnd;

            $payment = Payment::create([
                'contract_id' => $contract->id,
                'period_start' => $pStart->toDateString(),
                'period_end' => $dueDate->toDateString(),
                'amount' => $amount,
                'currency' => $contract->currency,
                'due_date' => $dueDate->toDateString(),
            ]);
            DomainEvent::afterCommit('payment.due', $payment, ['actor' => ActorType::System]);
            $created++;
        }

        return $created;
    }
}
