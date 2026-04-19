import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref, computed } from 'vue';

import { useProjectStore } from '~/stores/projectStore';

// Stub Nuxt auto-imports
const mockFetchProjects = vi.fn();
const mockFetchProject = vi.fn();
const mockCreateProject = vi.fn();
const mockUpdateProject = vi.fn();
const mockTransitionStatus = vi.fn();
const mockDeleteProject = vi.fn();

vi.stubGlobal('useProjects', () => ({
  fetchProjects: mockFetchProjects,
  fetchProject: mockFetchProject,
  createProject: mockCreateProject,
  updateProject: mockUpdateProject,
  transitionStatus: mockTransitionStatus,
  deleteProject: mockDeleteProject,
}));
vi.stubGlobal('ref', ref);
vi.stubGlobal('computed', computed);
vi.stubGlobal('useCookie', () => ({ value: null }));
vi.stubGlobal('useApi', () => ({ apiFetch: vi.fn() }));
vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: '' } }));
vi.stubGlobal('useI18n', () => ({ locale: { value: 'ar' } }));
vi.stubGlobal('useNuxtApp', () => ({ $i18n: { locale: { value: 'ar' } } }));
vi.stubGlobal('navigateTo', vi.fn());

describe('useProjectStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('loadProjects fetches and stores projects', async () => {
    mockFetchProjects.mockResolvedValue({
      data: [{ id: 1, name_en: 'P1' }],
      meta: { current_page: 1, last_page: 2, per_page: 15, total: 20 },
    });

    const store = useProjectStore();
    await store.loadProjects();

    expect(store.projects).toHaveLength(1);
    expect(store.total).toBe(20);
    expect(store.isLoading).toBe(false);
  });

  it('loadProject sets selectedProject', async () => {
    mockFetchProject.mockResolvedValue({ id: 5, name_en: 'Test' });

    const store = useProjectStore();
    await store.loadProject(5);

    expect(store.selectedProject?.id).toBe(5);
  });

  it('create adds project to list', async () => {
    mockCreateProject.mockResolvedValue({ id: 10, name_en: 'New' });

    const store = useProjectStore();
    await store.create({ name_ar: 'جديد', name_en: 'New' } as any);

    expect(store.projects[0].id).toBe(10);
  });

  it('update replaces project in list', async () => {
    const store = useProjectStore();
    store.projects = [{ id: 1, name_en: 'Old' } as any];
    mockUpdateProject.mockResolvedValue({ id: 1, name_en: 'Updated' });

    await store.update(1, { name_en: 'Updated' });

    expect(store.projects[0].name_en).toBe('Updated');
  });

  it('remove deletes project from list', async () => {
    const store = useProjectStore();
    store.projects = [{ id: 1 } as any, { id: 2 } as any];
    mockDeleteProject.mockResolvedValue({ success: true });

    await store.remove(1);

    expect(store.projects).toHaveLength(1);
    expect(store.projects[0].id).toBe(2);
  });

  it('setFilters updates filters', () => {
    const store = useProjectStore();
    store.setFilters({ status: 'draft' as any, city: 'Riyadh' });
    expect(store.filters.status).toBe('draft');
    expect(store.filters.city).toBe('Riyadh');
  });

  it('sets error on fetch failure', async () => {
    mockFetchProjects.mockRejectedValue(new Error('Network error'));

    const store = useProjectStore();
    await expect(store.loadProjects()).rejects.toThrow('Network error');
    expect(store.error).toBe('Network error');
    expect(store.isLoading).toBe(false);
  });
});
