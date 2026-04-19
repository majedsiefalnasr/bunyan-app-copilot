import { beforeEach, describe, expect, it, vi } from 'vitest';

import { useProjectPhases } from '~/composables/useProjectPhases';

const mockApiFetch = vi.fn();
vi.stubGlobal('useApi', () => ({ apiFetch: mockApiFetch }));
vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: '' } }));
vi.stubGlobal('useCookie', () => ({ value: 'test-token' }));
vi.stubGlobal('useI18n', () => ({ locale: { value: 'ar' } }));
vi.stubGlobal('useNuxtApp', () => ({ $i18n: { locale: { value: 'ar' } } }));
vi.stubGlobal('navigateTo', vi.fn());

describe('useProjectPhases', () => {
  beforeEach(() => {
    mockApiFetch.mockReset();
  });

  it('fetchPhases calls correct URL', async () => {
    mockApiFetch.mockResolvedValue({ success: true, data: [] });

    const { fetchPhases } = useProjectPhases();
    await fetchPhases(5);

    expect(mockApiFetch.mock.calls[0][0]).toContain('/api/v1/projects/5/phases');
  });

  it('createPhase sends POST', async () => {
    mockApiFetch.mockResolvedValue({ success: true, data: { id: 1 } });

    const { createPhase } = useProjectPhases();
    await createPhase(5, { name_ar: 'مرحلة', name_en: 'Phase', sort_order: 0 } as any);

    expect(mockApiFetch.mock.calls[0][1]).toEqual(expect.objectContaining({ method: 'POST' }));
  });

  it('updatePhase sends PUT', async () => {
    mockApiFetch.mockResolvedValue({ success: true, data: { id: 1 } });

    const { updatePhase } = useProjectPhases();
    await updatePhase(5, 1, { name_en: 'Updated' } as any);

    expect(mockApiFetch.mock.calls[0][0]).toContain('/api/v1/projects/5/phases/1');
    expect(mockApiFetch.mock.calls[0][1]).toEqual(expect.objectContaining({ method: 'PUT' }));
  });

  it('deletePhase sends DELETE', async () => {
    mockApiFetch.mockResolvedValue({ success: true, data: null });

    const { deletePhase } = useProjectPhases();
    await deletePhase(5, 1);

    expect(mockApiFetch.mock.calls[0][0]).toContain('/api/v1/projects/5/phases/1');
    expect(mockApiFetch.mock.calls[0][1]).toEqual(expect.objectContaining({ method: 'DELETE' }));
  });

  it('fetchTimeline calls correct URL', async () => {
    mockApiFetch.mockResolvedValue({ success: true, data: {} });

    const { fetchTimeline } = useProjectPhases();
    await fetchTimeline(5);

    expect(mockApiFetch.mock.calls[0][0]).toContain('/api/v1/projects/5/timeline');
  });
});
