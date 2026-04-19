import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useProjects } from '~/composables/useProjects';

// Stub Nuxt auto-imports
const mockApiFetch = vi.fn();
vi.stubGlobal('useApi', () => ({ apiFetch: mockApiFetch }));
vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: '' } }));
vi.stubGlobal('useCookie', () => ({ value: 'test-token' }));
vi.stubGlobal('useI18n', () => ({ locale: { value: 'ar' } }));
vi.stubGlobal('useNuxtApp', () => ({ $i18n: { locale: { value: 'ar' } } }));
vi.stubGlobal('navigateTo', vi.fn());

describe('useProjects', () => {
  beforeEach(() => {
    mockApiFetch.mockReset();
  });

  it('fetchProjects calls apiFetch with correct URL and returns data', async () => {
    const mockResponse = {
      success: true,
      data: [{ id: 1 }],
      meta: { current_page: 1, last_page: 1, per_page: 15, total: 1 },
    };
    mockApiFetch.mockResolvedValue(mockResponse);

    const { fetchProjects } = useProjects();
    const result = await fetchProjects({ status: 'draft' }, 1, 15);

    expect(mockApiFetch).toHaveBeenCalledOnce();
    const url = mockApiFetch.mock.calls[0][0] as string;
    expect(url).toContain('/api/v1/projects');
    expect(url).toContain('status=draft');
    expect(result).toEqual(mockResponse);
  });

  it('fetchProject calls apiFetch with project id', async () => {
    mockApiFetch.mockResolvedValue({ success: true, data: { id: 5, name_en: 'Test' } });

    const { fetchProject } = useProjects();
    const result = await fetchProject(5);

    expect(mockApiFetch.mock.calls[0][0]).toContain('/api/v1/projects/5');
    expect(result).toEqual({ id: 5, name_en: 'Test' });
  });

  it('createProject sends POST with body', async () => {
    mockApiFetch.mockResolvedValue({ success: true, data: { id: 1 } });

    const { createProject } = useProjects();
    await createProject({
      name_ar: 'مشروع',
      name_en: 'Project',
      city: 'Riyadh',
      type: 'residential',
    } as any);

    expect(mockApiFetch.mock.calls[0][1]).toEqual(expect.objectContaining({ method: 'POST' }));
  });

  it('updateProject sends PUT with partial data', async () => {
    mockApiFetch.mockResolvedValue({ success: true, data: { id: 1 } });

    const { updateProject } = useProjects();
    await updateProject(1, { name_en: 'Updated' });

    expect(mockApiFetch.mock.calls[0][0]).toContain('/api/v1/projects/1');
    expect(mockApiFetch.mock.calls[0][1]).toEqual(expect.objectContaining({ method: 'PUT' }));
  });

  it('transitionStatus sends PUT with status and expected_updated_at', async () => {
    mockApiFetch.mockResolvedValue({ success: true, data: { id: 1, status: 'planning' } });

    const { transitionStatus } = useProjects();
    await transitionStatus(1, 'planning' as any, '2025-01-01T00:00:00Z');

    const body = mockApiFetch.mock.calls[0][1].body;
    expect(body.status).toBe('planning');
    expect(body.expected_updated_at).toBe('2025-01-01T00:00:00Z');
  });

  it('deleteProject sends DELETE', async () => {
    mockApiFetch.mockResolvedValue({ success: true, data: null });

    const { deleteProject } = useProjects();
    await deleteProject(3);

    expect(mockApiFetch.mock.calls[0][0]).toContain('/api/v1/projects/3');
    expect(mockApiFetch.mock.calls[0][1]).toEqual(expect.objectContaining({ method: 'DELETE' }));
  });
});
