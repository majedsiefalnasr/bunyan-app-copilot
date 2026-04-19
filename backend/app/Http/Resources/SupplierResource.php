<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\SupplierVerificationStatus;
use App\Models\SupplierProfile;
use Illuminate\Http\Request;

/** @mixin SupplierProfile */
class SupplierResource extends BaseApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_name_ar' => $this->company_name_ar,
            'company_name_en' => $this->company_name_en,
            'commercial_reg' => $this->commercial_reg,
            'tax_number' => $this->tax_number,
            'city' => $this->city,
            'district' => $this->district,
            'address' => $this->address,
            'phone' => $this->phone,
            'verification_status' => $this->verification_status instanceof SupplierVerificationStatus
                ? $this->verification_status->value
                : $this->verification_status,
            'verified_at' => $this->verified_at?->toISOString(),
            'verified_by' => $this->verified_by,
            'rating_avg' => $this->rating_avg,
            'total_ratings' => $this->total_ratings,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'logo' => $this->logo,
            'website' => $this->website,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
