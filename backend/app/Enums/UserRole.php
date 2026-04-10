<?php

namespace App\Enums;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case CONTRACTOR = 'contractor';
    case SUPERVISING_ARCHITECT = 'supervising_architect';
    case FIELD_ENGINEER = 'field_engineer';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::CUSTOMER => 'Customer',
            self::CONTRACTOR => 'Contractor',
            self::SUPERVISING_ARCHITECT => 'Supervising Architect',
            self::FIELD_ENGINEER => 'Field Engineer',
            self::ADMIN => 'Administrator',
        };
    }
}
