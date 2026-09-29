<?php

namespace App\Services;

use App\Enums\AssignmentStatus;
use App\Enums\BookingStatus;
use App\Enums\ContractStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Rating;

/**
 * التقييم: الزيارة بعد "مكتمل" مرة واحدة (للفريق)؛ العقد بعد انتهائه — تقييم لكل خادمة عملت فيه.
 */
class RatingService
{
    /** @param array{service_score: int, worker_score?: ?int, comment?: ?string} $data */
    public function rateBooking(Booking $booking, Customer $customer, array $data): Rating
    {
        if ($booking->status !== BookingStatus::Completed) {
            throw BusinessRuleException::make('RATING_NOT_ALLOWED_YET');
        }
        if ($booking->rating()->exists()) {
            throw BusinessRuleException::make('ALREADY_RATED');
        }

        // CR-3: تقييم الزيارة للفريق الذي نفّذها (worker_score = تقييم الفريق)
        $teamId = $booking->assignments()->where('status', AssignmentStatus::Accepted)->latest('id')->value('team_id');

        return Rating::create([
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'team_id' => $teamId,
            'service_score' => $data['service_score'],
            'worker_score' => $teamId ? ($data['worker_score'] ?? null) : null,
            'comment' => $data['comment'] ?? null,
        ]);
    }

    /** @param array{worker_id: int, service_score: int, worker_score?: ?int, comment?: ?string} $data */
    public function rateContract(Contract $contract, Customer $customer, array $data): Rating
    {
        if (! in_array($contract->status, [ContractStatus::Completed, ContractStatus::Terminated], true)) {
            throw BusinessRuleException::make('RATING_NOT_ALLOWED_YET');
        }
        if (! $contract->assignments()->where('worker_id', $data['worker_id'])->exists()) {
            throw BusinessRuleException::make('NOT_FOUND', status: 404);
        }
        if ($contract->ratings()->where('worker_id', $data['worker_id'])->exists()) {
            throw BusinessRuleException::make('ALREADY_RATED');
        }

        return Rating::create([
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'worker_id' => $data['worker_id'],
            'service_score' => $data['service_score'],
            'worker_score' => $data['worker_score'] ?? null,
            'comment' => $data['comment'] ?? null,
        ]);
    }
}
