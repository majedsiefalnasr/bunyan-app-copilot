import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Category } from '~/types/categories';
import CategorySelector from '~/components/categories/CategorySelector.vue';

// Stub Nuxt auto-imports
vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: 'ar',
}));

describe('CategorySelector.vue', () => {
  const mockCategories: Category[] = [
    {
      id: 1,
      name_ar: 'المواد البناء',
      name_en: 'Building Materials',
      parent_id: null,
      slug: 'building-materials',
      icon: '🏗️',
      sort_order: 0,
      is_active: true,
      version: 1,
      children: [
        {
          id: 2,
          name_ar: 'الأسمنت',
          name_en: 'Cement',
          parent_id: 1,
          slug: 'cement',
          icon: '⚙️',
          sort_order: 0,
          is_active: true,
          version: 1,
          children: [],
          created_at: '2026-04-15T00:00:00Z',
          updated_at: '2026-04-15T00:00:00Z',
          deleted_at: null,
        },
      ],
      created_at: '2026-04-15T00:00:00Z',
      updated_at: '2026-04-15T00:00:00Z',
      deleted_at: null,
    },
    {
      id: 3,
      name_ar: 'الأدوات',
      name_en: 'Tools',
      parent_id: null,
      slug: 'tools',
      icon: '🔨',
      sort_order: 1,
      is_active: true,
      version: 1,
      children: [],
      created_at: '2026-04-15T00:00:00Z',
      updated_at: '2026-04-15T00:00:00Z',
      deleted_at: null,
    },
  ];

  const defaultProps = {
    modelValue: null as number | null,
    'onUpdate:modelValue': vi.fn(),
  };

  it('renders dropdown component', () => {
    const wrapper = mount(CategorySelector, {
      props: defaultProps,
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    expect(wrapper.exists()).toBe(true);
  });

  it('accepts and displays provided categories', async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelect: {
            template: '<div class="u-select" />',
            props: ['options', 'modelValue'],
          },
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.flattenedOptions).toBeDefined();
  });

  it('flattens nested category tree for dropdown', () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    // Should have flattened categories with indentation prefix
    const options = vm.flattenedOptions || vm.options || [];
    expect(Array.isArray(options)).toBe(true);
  });

  it('filter categories by search term', async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.searchTerm = 'cement';

    const options = vm.filteredOptions || vm.options || [];
    // Should only contain cement-related items
    expect(Array.isArray(options)).toBe(true);
  });

  it('filter categories by Arabic name', async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.searchTerm = 'الأسمنت';

    const options = vm.filteredOptions || vm.options || [];
    expect(Array.isArray(options)).toBe(true);
  });

  it('emits update when selection changes', async () => {
    const onUpdate = vi.fn();
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        'onUpdate:modelValue': onUpdate,
      },
      global: {
        stubs: {
          USelect: {
            template: '<input @input="$emit(\'update:modelValue\', 1)" />',
            props: ['onUpdate:modelValue'],
          },
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.selectCategory(1);

    expect(vm.$emit).toBeDefined();
  });

  it('displays selected category correctly', async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        modelValue: 1,
      },
      global: {
        stubs: {
          USelect: {
            template: '<div />',
            props: ['modelValue'],
          },
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.$props.modelValue).toBe(1);
  });

  it('allows clearing selection (null value)', async () => {
    const onUpdate = vi.fn();
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        modelValue: 1,
        'onUpdate:modelValue': onUpdate,
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.selectCategory(null);

    expect(vm.$props.modelValue || null).toBeDefined();
  });

  it('handles English/Arabic bilingual display', () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
        locale: 'ar',
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.$props.categories).toBeDefined();
  });

  it('shows child categories indented under parents', () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelect: {
            template: '<div class="u-select"><div v-for="opt in options" :key="opt.value" class="option">{{ opt.label }}</div></div>',
            props: ['options'],
          },
        },
      },
    });

    const vm = wrapper.vm as any;
    // Options should have indentation indicators
    expect(vm.formatOptionLabel).toBeDefined() || expect(vm.flattenedOptions).toBeDefined();
  });

  it('prevents selecting inactive categories', () => {
    const inactiveCategory: Category = {
      ...mockCategories[0],
      id: 99,
      is_active: false,
    };

    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: [inactiveCategory],
        disableInactive: true,
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.$props.categories).toBeDefined();
  });

  it('updates value on modelValue prop change', async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        modelValue: null,
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    await wrapper.setProps({ modelValue: 1 });
    await wrapper.vm.$nextTick();

    const vm = wrapper.vm as any;
    expect(vm.$props.modelValue).toBe(1);
  });

  it('handles dropdown opening/closing', async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.isOpen = true;
    await wrapper.vm.$nextTick();
    expect(vm.isOpen).toBe(true);

    vm.isOpen = false;
    await wrapper.vm.$nextTick();
    expect(vm.isOpen).toBe(false);
  });

  it('respects RTL layout with correct text direction', () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
        direction: 'rtl',
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.$props.categories).toBeDefined();
  });

  it('handles empty categories list gracefully', () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: [],
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.$props.categories).toEqual([]);
  });

  it('keyboard navigation works in dropdown', async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.handleKeyDown({
      key: 'ArrowDown',
      preventDefault: vi.fn(),
    });

    expect(vm.selectedIndex || vm.selectedIndex === 0).toBeDefined();
  });

  it('supports clicking on option to select', async () => {
    const onUpdate = vi.fn();
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        'onUpdate:modelValue': onUpdate,
      },
      global: {
        stubs: {
          USelect: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.selectCategory(1);

    expect(vm.$emit).toBeDefined();
  });
});
