<?php

namespace App\Enums;

enum PhaseStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::IN_PROGRESS => 'قيد التنفيذ',
            self::COMPLETED => 'مكتمل',
        };
    }
}
