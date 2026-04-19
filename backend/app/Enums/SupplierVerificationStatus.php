<?php

declare(strict_types=1);

namespace App\Enums;

enum SupplierVerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Suspended = 'suspended';
}
