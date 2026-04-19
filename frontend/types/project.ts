// frontend/types/project.ts
// Project system types

export enum ProjectStatus {
  Draft = 'draft',
  Planning = 'planning',
  InProgress = 'in_progress',
  OnHold = 'on_hold',
  Completed = 'completed',
  Closed = 'closed',
}

export enum ProjectType {
  Residential = 'residential',
  Commercial = 'commercial',
  Infrastructure = 'infrastructure',
}

export enum PhaseStatus {
  Pending = 'pending',
  InProgress = 'in_progress',
  Completed = 'completed',
}

export interface Project {
  id: number;
  owner_id: number;
  owner?: {
    id: number;
    name: string;
    email: string;
    role: string;
  };
  name_ar: string;
  name_en: string;
  description: string | null;
  city: string;
  district: string | null;
  location_lat: string | null;
  location_lng: string | null;
  status: ProjectStatus;
  type: ProjectType;
  budget_estimated: string | null;
  budget_actual: string | null;
  start_date: string | null;
  end_date: string | null;
  phases_count?: number;
  phases?: ProjectPhase[];
  created_at: string;
  updated_at: string;
}

export interface ProjectPhase {
  id: number;
  project_id: number;
  name_ar: string;
  name_en: string;
  sort_order: number;
  status: PhaseStatus;
  start_date: string | null;
  end_date: string | null;
  completion_percentage: number;
  created_at: string;
  updated_at: string;
}

export interface ProjectFormData {
  owner_id: number;
  name_ar: string;
  name_en: string;
  description?: string;
  city: string;
  district?: string;
  location_lat?: number;
  location_lng?: number;
  type: ProjectType;
  budget_estimated?: number;
  start_date?: string;
  end_date?: string;
}

export interface PhaseFormData {
  name_ar: string;
  name_en: string;
  sort_order: number;
  status?: PhaseStatus;
  start_date?: string;
  end_date?: string;
  completion_percentage?: number;
}

export interface ProjectFilters {
  status?: ProjectStatus;
  type?: ProjectType;
  city?: string;
}

export interface TimelineData {
  project: {
    id: number;
    name_ar: string;
    name_en: string;
    start_date: string | null;
    end_date: string | null;
    status: string;
  };
  phases: Array<{
    id: number;
    name_ar: string;
    name_en: string;
    start_date: string | null;
    end_date: string | null;
    status: string;
    completion_percentage: number;
    sort_order: number;
  }>;
}

export interface ProjectListResponse {
  success: boolean;
  data: Project[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  error: null;
}
