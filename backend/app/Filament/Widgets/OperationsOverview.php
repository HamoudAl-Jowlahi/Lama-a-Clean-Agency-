<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\ChangeRequestStatus;
use App\Enums\ComplaintStatus;
use App\Enums\ContractStatus;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\ChangeRequests\ChangeRequestResource;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Resources\Contracts\ContractResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Support\Format;
use App\Models\Booking;
use App\Models\Complaint;
use App\Models\Contract;
use App\Models\ContractChangeRequest;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** مؤشرات اليوم في الصفحة الرئيسية للوحة — كل بطاقة تفتح القسم المعني. */
class OperationsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $today = now(config('agency.timezone'))->toDateString();

        $visitsToday = Booking::whereDate('scheduled_date', $today)
            ->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::Rejected])->count();
        $doneToday = Booking::whereDate('scheduled_date', $today)->where('status', BookingStatus::Completed)->count();
        $pendingVisits = Booking::where('status', BookingStatus::Pending)->count();
        $toAssign = Booking::where('status', BookingStatus::Confirmed)->count();
        $pendingContracts = Contract::where('status', ContractStatus::Pending)->count();
        $activeContracts = Contract::where('status', ContractStatus::Active)->count();
        $openRequests = ContractChangeRequest::whereIn('status', [ChangeRequestStatus::Open, ChangeRequestStatus::UnderReview])->count();
        $openComplaints = Complaint::whereIn('status', [ComplaintStatus::Open, ComplaintStatus::UnderReview])->count();
        $due = Payment::due();

        return [
            Stat::make('زيارات اليوم', $visitsToday)
                ->description("{$doneToday} مكتملة")
                ->icon('heroicon-o-calendar-days')
                ->url(BookingResource::getUrl('index')),
            Stat::make('بانتظار المراجعة', $pendingVisits + $pendingContracts)
                ->description("{$pendingVisits} زيارة · {$pendingContracts} عقد · {$toAssign} بانتظار الإسناد")
                ->color($pendingVisits + $pendingContracts > 0 ? 'warning' : 'gray')
                ->icon('heroicon-o-inbox')
                ->url(BookingResource::getUrl('index', ['filters' => ['needs_action' => ['isActive' => true]]])),
            Stat::make('عقود سارية', $activeContracts)
                ->icon('heroicon-o-document-text')
                ->url(ContractResource::getUrl('index')),
            Stat::make('طلبات استبدال / إنهاء', $openRequests)
                ->description("و {$openComplaints} شكاوى مفتوحة")
                ->color($openRequests + $openComplaints > 0 ? 'danger' : 'gray')
                ->icon('heroicon-o-arrows-right-left')
                ->url($openRequests > 0 ? ChangeRequestResource::getUrl('index') : ComplaintResource::getUrl('index')),
            Stat::make('مستحق نقداً', Format::money((clone $due)->sum('amount'), config('agency.currency')))
                ->description((clone $due)->count().' استحقاقات غير محصّلة')
                ->icon('heroicon-o-banknotes')
                ->url(PaymentResource::getUrl('index')),
        ];
    }
}
