<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case DRAFT = 'draft';
    case PLANNING = 'planning';
    case IN_PROGRESS = 'in_progress';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'مسودة',
            self::PLANNING => 'تخطيط',
            self::IN_PROGRESS => 'قيد التنفيذ',
            self::ON_HOLD => 'متوقف',
            self::COMPLETED => 'مكتمل',
            self::CLOSED => 'مغلق',
        };
    }

    /** @return ProjectStatus[] */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PLANNING],
            self::PLANNING => [self::IN_PROGRESS],
            self::IN_PROGRESS => [self::ON_HOLD, self::COMPLETED],
            self::ON_HOLD => [self::IN_PROGRESS],
            self::COMPLETED => [self::CLOSED],
            self::CLOSED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
