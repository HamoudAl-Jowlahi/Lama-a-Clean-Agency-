<?php

namespace App\Enums;

/**
 * CR-3: نوعان منفصلان من الموظفين.
 * cleaner: عضو في فريق زيارات (زيارات فقط) · housekeeper: خادمة (عقود فقط).
 */
enum WorkerType: string
{
    use EnumHelpers;

    public const LANG_KEY = 'worker_type';

    case Cleaner = 'cleaner';
    case Housekeeper = 'housekeeper';
}
