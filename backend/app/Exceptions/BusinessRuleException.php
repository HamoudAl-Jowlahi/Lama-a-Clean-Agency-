<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * خرق قاعدة عمل (انتقال حالة غير مسموح، عاملة غير متاحة، ...).
 * يُعرض في الـ API كـ {message, code} بالحالة المحددة (409 افتراضياً).
 *
 * الرسالة تُقرأ من lang/{locale}/api.php بالمفتاح errors.{code}.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status = 409,
        public readonly array $replace = [],
    ) {
        parent::__construct($errorCode);
    }

    public static function make(string $code, array $replace = [], int $status = 409): self
    {
        return new self($code, $status, $replace);
    }

    public function userMessage(): string
    {
        $key = 'api.errors.'.$this->errorCode;
        $message = __($key, $this->replace);

        return $message === $key ? __('api.errors.GENERIC') : $message;
    }
}
