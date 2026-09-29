<?php

namespace App\StateMachines;

use App\Enums\ActorType;
use App\Events\StatusChanged;
use App\Exceptions\BusinessRuleException;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * المكان الوحيد الذي تتغير فيه الحالة. كل انتقال:
 *  1) يُقفل السجل (lockForUpdate) ويقرأ حالته الحالية من قاعدة البيانات،
 *  2) يتحقق أن الانتقال مسموح لهذا الفاعل،
 *  3) يحفظ الحالة + الحقول المصاحبة، ويسجل status_logs، ويطلق StatusChanged.
 */
abstract class StateMachine
{
    /**
     * [from => [to => [ActorType, ...]]]
     *
     * @return array<string, array<string, list<ActorType>>>
     */
    abstract protected function transitions(): array;

    /** @return class-string<BackedEnum> */
    abstract protected function statusEnum(): string;

    public function can(Model $model, BackedEnum $to, ActorType $actor): bool
    {
        return in_array($actor, $this->transitions()[$model->status->value][$to->value] ?? [], true);
    }

    /** الحالات التالية المسموحة لهذا الفاعل (للواجهات: أي زر يظهر). */
    public function nextFor(Model $model, ActorType $actor): array
    {
        $enum = $this->statusEnum();

        return collect($this->transitions()[$model->status->value] ?? [])
            ->filter(fn (array $actors) => in_array($actor, $actors, true))
            ->keys()
            ->map(fn (string $to) => $enum::from($to))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $attributes  حقول تُحفظ مع الانتقال (confirmed_at، cancel_reason، ...)
     */
    public function transition(
        Model $model,
        BackedEnum $to,
        ActorType $actor,
        ?int $actorId = null,
        ?string $note = null,
        array $attributes = [],
    ): Model {
        return DB::transaction(function () use ($model, $to, $actor, $actorId, $note, $attributes) {
            $locked = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());
            $from = $locked->status;

            if (! $this->can($locked, $to, $actor)) {
                throw BusinessRuleException::make('INVALID_TRANSITION', [
                    'from' => $from->label(),
                    'to' => $to->label(),
                ]);
            }

            $locked->forceFill(['status' => $to] + $attributes)->save();

            $locked->statusLogs()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'actor_type' => $actor,
                'actor_id' => $actorId,
                'note' => $note,
            ]);

            // نعيد نفس الكائن محدثاً حتى لا يعمل المستدعي على نسخة قديمة
            $model->setRawAttributes($locked->getAttributes(), true);

            DB::afterCommit(fn () => event(new StatusChanged($model, $from, $to, $actor, $actorId)));

            return $model;
        });
    }
}
