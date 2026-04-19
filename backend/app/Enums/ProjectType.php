<?php

namespace App\Enums;

enum ProjectType: string
{
    case RESIDENTIAL = 'residential';
    case COMMERCIAL = 'commercial';
    case INFRASTRUCTURE = 'infrastructure';

    public function label(): string
    {
        return match ($this) {
            self::RESIDENTIAL => 'سكني',
            self::COMMERCIAL => 'تجاري',
            self::INFRASTRUCTURE => 'بنية تحتية',
        };
    }
}
