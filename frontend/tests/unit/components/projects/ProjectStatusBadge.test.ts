import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import ProjectStatusBadge from '~/components/projects/ProjectStatusBadge.vue';

vi.stubGlobal('useI18n', () => ({ t: (k: string) => k, locale: ref('ar') }));

const stubComponents = {
  UBadge: { template: '<span :class="$attrs.class"><slot /></span>', props: ['color', 'variant'] },
};

describe('ProjectStatusBadge', () => {
  it('renders with draft status', () => {
    const wrapper = mount(ProjectStatusBadge, {
      props: { status: 'draft' },
      global: { stubs: stubComponents },
    });
    expect(wrapper.exists()).toBe(true);
  });

  it('renders with in_progress status', () => {
    const wrapper = mount(ProjectStatusBadge, {
      props: { status: 'in_progress' },
      global: { stubs: stubComponents },
    });
    expect(wrapper.exists()).toBe(true);
  });

  it('renders with completed status', () => {
    const wrapper = mount(ProjectStatusBadge, {
      props: { status: 'completed' },
      global: { stubs: stubComponents },
    });
    expect(wrapper.exists()).toBe(true);
  });
});
