<?php

namespace App\Http\Requests;

use App\Support\Settings;
use Illuminate\Foundation\Http\FormRequest;

/**
 * أساس كل طلبات الـ API: الصلاحية تُفحص في الـ middleware (role) والـ scoping،
 * وهنا التحقق من شكل المدخلات فقط.
 */
abstract class ApiRequest extends FormRequest
{
    public const PHONE_REGEX = '/^\+?[0-9]{9,15}$/';

    public function authorize(): bool
    {
        return true;
    }

    /** قواعد المرفقات المشتركة (صور/PDF). */
    protected function attachmentRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:'.implode(',', Settings::get('attachments.mimes')), 'max:'.Settings::get('attachments.max_kb')],
        ];
    }

    /** @return list<\Illuminate\Http\UploadedFile> */
    public function attachments(): array
    {
        return $this->file('attachments', []);
    }
}
