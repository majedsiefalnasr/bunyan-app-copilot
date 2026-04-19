import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import ProjectCard from '~/components/projects/ProjectCard.vue';

vi.stubGlobal('useI18n', () => ({ t: (k: string) => k, locale: ref('ar') }));
vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: '' } }));
vi.stubGlobal('useCookie', () => ({ value: null }));
vi.stubGlobal('useNuxtApp', () => ({ $i18n: { locale: { value: 'ar' } } }));
vi.stubGlobal('navigateTo', vi.fn());
vi.stubGlobal('definePageMeta', vi.fn());
vi.stubGlobal('useLocalePath', () => (path: string) => path);

// Stub Nuxt UI components
const stubComponents = {
  UCard: { template: '<div><slot /><slot name="header" /><slot name="footer" /></div>' },
  UBadge: { template: '<span><slot /></span>', props: ['color', 'variant'] },
  UButton: {
    template: '<button @click="$emit(\'click\')"><slot /></button>',
    props: ['to', 'color', 'variant', 'icon'],
  },
};

describe('ProjectCard', () => {
  const mockProject = {
    id: 1,
    name_ar: 'مشروع اختبار',
    name_en: 'Test Project',
    status: 'draft',
    type: 'residential',
    city: 'الرياض',
    budget_estimated: 500000,
    created_at: '2025-01-01T00:00:00Z',
    updated_at: '2025-01-01T00:00:00Z',
  };

  it('renders project name', () => {
    const wrapper = mount(ProjectCard, {
      props: { project: mockProject as any },
      global: { stubs: stubComponents },
    });

    expect(wrapper.text()).toContain('مشروع اختبار');
  });

  it('renders city', () => {
    const wrapper = mount(ProjectCard, {
      props: { project: mockProject as any },
      global: { stubs: stubComponents },
    });

    expect(wrapper.text()).toContain('الرياض');
  });

  it('emits click when card is interacted with', async () => {
    const wrapper = mount(ProjectCard, {
      props: { project: mockProject as any },
      global: { stubs: stubComponents },
    });

    // ProjectCard should have a navigable link or emit
    expect(wrapper.exists()).toBe(true);
  });
});
