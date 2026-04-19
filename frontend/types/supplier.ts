export type SupplierVerificationStatus = 'pending' | 'verified' | 'suspended';

export interface SupplierProfile {
  id: number;
  user_id: number;
  company_name_ar: string;
  company_name_en: string;
  commercial_reg: string;
  tax_number: string | null;
  city: string;
  district: string | null;
  address: string | null;
  phone: string;
  verification_status: SupplierVerificationStatus;
  verified_at: string | null;
  verified_by: number | null;
  rating_avg: string;
  total_ratings: number;
  description_ar: string | null;
  description_en: string | null;
  logo: string | null;
  website: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface SupplierListFilters {
  city?: string;
  district?: string;
  search?: string;
  verification_status?: SupplierVerificationStatus;
  per_page?: number;
  page?: number;
}

export interface StoreSupplierPayload {
  company_name_ar: string;
  company_name_en: string;
  commercial_reg: string;
  phone: string;
  city: string;
  tax_number?: string | null;
  district?: string | null;
  address?: string | null;
  description_ar?: string | null;
  description_en?: string | null;
  logo?: string | null;
  website?: string | null;
  user_id?: number | null;
}

export interface UpdateSupplierPayload {
  company_name_ar?: string;
  company_name_en?: string;
  commercial_reg?: string;
  phone?: string;
  city?: string;
  tax_number?: string | null;
  district?: string | null;
  address?: string | null;
  description_ar?: string | null;
  description_en?: string | null;
  logo?: string | null;
  website?: string | null;
}

export interface PaginatedSuppliers {
  data: SupplierProfile[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}
