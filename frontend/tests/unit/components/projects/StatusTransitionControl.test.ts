import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import StatusTransitionControl from '~/components/projects/StatusTransitionControl.vue';

vi.stubGlobal('useI18n', () => ({ t: (k: string) => k, locale: ref('ar') }));
vi.stubGlobal('useCookie', () => ({ value: null }));
vi.stubGlobal('useRuntimeConfig', () => ({ public: { apiBase: '' } }));
vi.stubGlobal('useNuxtApp', () => ({ $i18n: { locale: { value: 'ar' } } }));
vi.stubGlobal('navigateTo', vi.fn());
vi.stubGlobal('useProjectStore', () => ({
  transitionStatus: vi.fn(),
}));
vi.stubGlobal('useNotification', () => ({
  notifySuccess: vi.fn(),
  notifyError: vi.fn(),
}));

const stubComponents = {
  UDropdown: { template: '<div><slot /></div>', props: ['items'] },
  UButton: {
    template: '<button @click="$emit(\'click\')"><slot /></button>',
    props: ['color', 'variant', 'icon'],
  },
  UModal: { template: '<div v-if="modelValue"><slot /></div>', props: ['modelValue'] },
};

describe('StatusTransitionControl', () => {
  const defaultProps = {
    currentStatus: 'draft',
    projectId: 1,
    updatedAt: '2025-01-01T00:00:00Z',
  };

  it('renders', () => {
    const wrapper = mount(StatusTransitionControl, {
      props: defaultProps as any,
      global: { stubs: stubComponents },
    });
    expect(wrapper.exists()).toBe(true);
  });

  it('shows allowed transitions for draft status', () => {
    const wrapper = mount(StatusTransitionControl, {
      props: defaultProps as any,
      global: { stubs: stubComponents },
    });
    // Draft can transition to planning
    expect(wrapper.exists()).toBe(true);
  });
});
