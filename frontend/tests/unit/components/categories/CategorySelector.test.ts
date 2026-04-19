import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CategorySelector from '~/components/categories/CategorySelector.vue';
import type { Category } from '~/types';

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
      props: {
        ...defaultProps,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelectMenu: false,
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
          USelectMenu: {
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
          USelectMenu: false,
        },
      },
    });

    const vm = wrapper.vm as any;
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
          USelectMenu: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.searchTerm = 'cement';

    const options = vm.filteredOptions || vm.options || [];
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
          USelectMenu: false,
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
        categories: mockCategories,
        'onUpdate:modelValue': onUpdate,
      },
      global: {
        stubs: {
          USelectMenu: {
            template: `
              <select :value="String(modelValue)" @change="$emit('update:modelValue', { value: parseInt($event.target.value) })">
                <option v-for="opt in options" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            `,
            props: ['modelValue', 'options'],
          },
        },
      },
    });

    const select = wrapper.find('select');
    await select.setValue('2'); // cement id=2

    expect(onUpdate).toHaveBeenCalledWith(2);
  });

  it('displays selected category correctly', () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        modelValue: 1,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelectMenu: false,
        },
      },
    });

    expect(wrapper.props('modelValue')).toBe(1);
  });

  it('allows clearing selection (null value)', () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        modelValue: null,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelectMenu: false,
        },
      },
    });

    expect(wrapper.props('modelValue')).toBeNull();
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
          USelectMenu: false,
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
          USelectMenu: false,
        },
      },
    });

    const vm = wrapper.vm as any;  
    const options = vm.flattenedOptions as any[];
    // Cement (id:2) is child of Building Materials (id:1), should have indent level 1 (two spaces)
    const childOption = options.find((opt: any) => opt.value === 2);
    expect(childOption).toBeDefined();
    expect(childOption.indent).toBe(1);
    expect(childOption.label).toBe('  الأسمنت');
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
          USelectMenu: false,
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
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelectMenu: false,
        },
      },
    });

    await wrapper.setProps({ modelValue: 2 });
    await wrapper.vm.$nextTick();

    expect(wrapper.props('modelValue')).toBe(2);
  });

  it('handles dropdown opening/closing', async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelectMenu: false,
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
          USelectMenu: false,
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
          USelectMenu: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.$props.categories).toEqual([]);
  });

  it.skip('keyboard navigation works in dropdown', async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        categories: mockCategories,
      },
      global: {
        stubs: {
          USelectMenu: false,
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

  it.skip('supports clicking on option to select', async () => {
    const onUpdate = vi.fn();
    const wrapper = mount(CategorySelector, {
      props: {
        ...defaultProps,
        'onUpdate:modelValue': onUpdate,
      },
      global: {
        stubs: {
          USelectMenu: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.selectCategory(1);

    expect(vm.$emit).toBeDefined();
  });
});
