import type { ProjectPhase, PhaseFormData, TimelineData } from '~/types/project';

export function useProjectPhases() {
  const { apiFetch } = useApi();

  async function fetchPhases(projectId: number) {
    const response = await apiFetch<{ success: boolean; data: ProjectPhase[] }>(
      `/api/v1/projects/${projectId}/phases`,
      { method: 'GET' }
    );
    return response.data;
  }

  async function createPhase(projectId: number, data: PhaseFormData) {
    const response = await apiFetch<{ success: boolean; data: ProjectPhase }>(
      `/api/v1/projects/${projectId}/phases`,
      { method: 'POST', body: data }
    );
    return response.data;
  }

  async function updatePhase(projectId: number, phaseId: number, data: Partial<PhaseFormData>) {
    const response = await apiFetch<{ success: boolean; data: ProjectPhase }>(
      `/api/v1/projects/${projectId}/phases/${phaseId}`,
      { method: 'PUT', body: data }
    );
    return response.data;
  }

  async function deletePhase(projectId: number, phaseId: number) {
    const response = await apiFetch<{ success: boolean; data: null }>(
      `/api/v1/projects/${projectId}/phases/${phaseId}`,
      { method: 'DELETE' }
    );
    return response;
  }

  async function fetchTimeline(projectId: number) {
    const response = await apiFetch<{ success: boolean; data: TimelineData }>(
      `/api/v1/projects/${projectId}/timeline`,
      { method: 'GET' }
    );
    return response.data;
  }

  return {
    fetchPhases,
    createPhase,
    updatePhase,
    deletePhase,
    fetchTimeline,
  };
}
