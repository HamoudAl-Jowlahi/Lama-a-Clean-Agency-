<?php

namespace App\Models\Concerns;

/**
 * رقم مقروء مثل BK-2026-000123 مشتق من الـ id بعد الإنشاء مباشرة:
 * فريد دون جدول تسلسل ودون تعارض بين الطلبات المتزامنة.
 *
 * الكلاس المستخدم يعرّف: DOCUMENT_PREFIX و DOCUMENT_NUMBER_COLUMN.
 */
trait HasDocumentNumber
{
    public static function bootHasDocumentNumber(): void
    {
        static::created(function ($model) {
            $column = static::DOCUMENT_NUMBER_COLUMN;
            if ($model->{$column} === null) {
                $model->{$column} = static::formatDocumentNumber($model->id, $model->created_at?->year ?? now()->year);
                $model->saveQuietly();
            }
        });
    }

    public static function formatDocumentNumber(int $id, int $year): string
    {
        return sprintf('%s-%d-%06d', static::DOCUMENT_PREFIX, $year, $id);
    }
}
