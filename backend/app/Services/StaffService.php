<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WorkerStatus;
use App\Enums\WorkerType;
use App\Exceptions\BusinessRuleException;
use App\Models\AdminUser;
use App\Models\User;
use App\Models\Worker;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * حسابات الموظفين (من لوحة الإدارة فقط — لا تسجيل ذاتي للموظف)،
 * وإيقاف/تفعيل حسابات التطبيق.
 */
class StaffService
{
    /**
     * @param  array{name: string, phone: string, password: string, email?: ?string, type: string, status?: ?string, national_id?: ?string, notes?: ?string}  $data
     */
    public function createWorker(array $data, AdminUser $admin): Worker
    {
        return DB::transaction(function () use ($data, $admin) {
            $user = new User([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
            ]);
            $user->role = UserRole::Worker; // الدور يحدده الخادم
            $user->save();

            $worker = $user->worker()->create([
                'type' => self::enum(WorkerType::class, $data['type']),
                'status' => self::enum(WorkerStatus::class, $data['status'] ?? WorkerStatus::Active),
                'national_id' => $data['national_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            AuditLogger::log($admin, 'worker.created', $worker, new: ['name' => $user->name, 'type' => $worker->type->value]);

            return $worker;
        });
    }

    /**
     * @param  array{name?: string, phone?: string, password?: ?string, email?: ?string, type?: string, status?: string, national_id?: ?string, notes?: ?string}  $data
     */
    public function updateWorker(Worker $worker, array $data, AdminUser $admin): Worker
    {
        $worker->loadMissing('user');
        $type = isset($data['type']) ? self::enum(WorkerType::class, $data['type']) : null;
        $status = isset($data['status']) ? self::enum(WorkerStatus::class, $data['status']) : null;

        if ($type && $type !== $worker->type) {
            // لا يتغير النوع وهو مرتبط بعمل من النوع الحالي
            if ($worker->teams()->exists()) {
                throw BusinessRuleException::make('WORKER_IN_TEAM', status: 422);
            }
            if ($worker->currentContractAssignment()->exists()) {
                throw BusinessRuleException::make('WORKER_BUSY_CONTRACT', status: 422);
            }
        }

        return DB::transaction(function () use ($worker, $data, $admin, $type, $status) {
            $old = ['name' => $worker->user->name, 'phone' => $worker->user->phone, 'type' => $worker->type->value, 'status' => $worker->status->value];

            $worker->user->fill(array_filter([
                'name' => $data['name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'] ?? null, // فارغ = بدون تغيير
            ]));
            if (array_key_exists('email', $data)) {
                $worker->user->email = $data['email'];
            }
            $worker->user->save();

            // رقم الهوية مخفي ولا يُعرض في النماذج: فارغ = الإبقاء على المحفوظ
            if (! empty($data['national_id'])) {
                $worker->national_id = $data['national_id'];
            }
            if (array_key_exists('notes', $data)) {
                $worker->notes = $data['notes'];
            }
            if ($type) {
                $worker->type = $type;
            }
            if ($status) {
                $worker->status = $status;
            }
            $worker->save();

            AuditLogger::log($admin, 'worker.updated', $worker, $old, [
                'name' => $worker->user->name, 'phone' => $worker->user->phone,
                'type' => $worker->type->value, 'status' => $worker->status->value,
                'password_changed' => ! empty($data['password']),
            ]);

            return $worker;
        });
    }

    /**
     * القيمة قد تصل نصاً (API) أو Enum جاهزاً (نماذج Filament).
     *
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $class
     * @return T
     */
    private static function enum(string $class, mixed $value): \BackedEnum
    {
        return $value instanceof $class ? $value : $class::from($value);
    }

    /** إيقاف أو تفعيل حساب تطبيق (عميل أو موظف). الإيقاف يلغي جلساته فوراً. */
    public function setUserStatus(User $user, UserStatus $status, AdminUser $admin): User
    {
        $old = $user->status;
        $user->status = $status;
        $user->save();

        if ($status === UserStatus::Suspended) {
            $user->tokens()->delete();
        }

        AuditLogger::log($admin, 'user.status_changed', $user, ['status' => $old->value], ['status' => $status->value]);

        return $user;
    }
}
