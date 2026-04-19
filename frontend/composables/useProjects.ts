import type { Project, ProjectFormData, ProjectFilters, ProjectStatus } from '~/types/project';

export function useProjects() {
  const { apiFetch } = useApi();

  async function fetchProjects(filters: ProjectFilters = {}, page = 1, perPage = 15) {
    const params = new URLSearchParams();
    if (filters.status) params.append('status', filters.status);
    if (filters.type) params.append('type', filters.type);
    if (filters.city) params.append('city', filters.city);
    params.append('page', String(page));
    params.append('per_page', String(perPage));

    const response = await apiFetch<{
      success: boolean;
      data: Project[];
      meta: { current_page: number; last_page: number; per_page: number; total: number };
    }>(`/api/v1/projects?${params.toString()}`, { method: 'GET' });

    return response;
  }

  async function fetchProject(id: number) {
    const response = await apiFetch<{ success: boolean; data: Project }>(`/api/v1/projects/${id}`, {
      method: 'GET',
    });
    return response.data;
  }

  async function createProject(data: ProjectFormData) {
    const response = await apiFetch<{ success: boolean; data: Project }>('/api/v1/projects', {
      method: 'POST',
      body: data,
    });
    return response.data;
  }

  async function updateProject(id: number, data: Partial<ProjectFormData>) {
    const response = await apiFetch<{ success: boolean; data: Project }>(`/api/v1/projects/${id}`, {
      method: 'PUT',
      body: data,
    });
    return response.data;
  }

  async function transitionStatus(id: number, status: ProjectStatus, expectedUpdatedAt: string) {
    const response = await apiFetch<{ success: boolean; data: Project }>(
      `/api/v1/projects/${id}/status`,
      { method: 'PUT', body: { status, expected_updated_at: expectedUpdatedAt } }
    );
    return response.data;
  }

  async function deleteProject(id: number) {
    const response = await apiFetch<{ success: boolean; data: null }>(`/api/v1/projects/${id}`, {
      method: 'DELETE',
    });
    return response;
  }

  return {
    fetchProjects,
    fetchProject,
    createProject,
    updateProject,
    transitionStatus,
    deleteProject,
  };
}
