// frontend/types/categories.ts
// Category system types

export interface Category {
  id: number;
  parent_id: number | null;
  name_ar: string;
  name_en: string;
  slug: string;
  icon?: string;
  sort_order: number;
  is_active: boolean;
  version: number;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  children?: Category[];
}

export interface CategoryFormData {
  name_ar: string;
  name_en: string;
  parent_id?: number | null;
  icon?: string;
  is_active?: boolean;
  sort_order?: number;
}

export interface CategoryUpdateData extends CategoryFormData {
  version: number;
}
