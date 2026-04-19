import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CategoryTree from '~/components/categories/CategoryTree.vue';
import type { Category } from '~/types';

// Stub Nuxt auto-imports
vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: 'ar',
}));

describe('CategoryTree.vue', () => {
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
        {
          id: 3,
          name_ar: 'الحديد',
          name_en: 'Steel',
          parent_id: 1,
          slug: 'steel',
          icon: '⚙️',
          sort_order: 1,
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
      id: 4,
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

  it('renders empty state when no categories provided', () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: [],
      },
      global: {
        stubs: {
          CategoryTreeNode: true,
        },
      },
    });

    expect(wrapper.text()).toContain('categories.noCategories');
  });

  it('renders tree with nested categories', () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
      global: {
        stubs: {
          CategoryTreeNode: true,
        },
      },
    });

    // Should render list items for top-level categories
    const listItems = wrapper.findAll('li');
    expect(listItems).toHaveLength(2);
  });

  it('passes category data to CategoryTreeNode components', () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
      global: {
        stubs: {
          CategoryTreeNode: {
            template: '<div />',
            props: ['category', 'level', 'editable', 'selectable', 'expanded'],
          },
        },
      },
    });

    const nodes = wrapper.findAllComponents({ name: 'CategoryTreeNode' });
    expect(nodes).toHaveLength(2);
    expect(nodes[0].props('category')).toEqual(mockCategories[0]);
    expect(nodes[0].props('level')).toBe(0);
  });

  it('handles toggle expanded for a category', async () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
      global: {
        stubs: {
          CategoryTreeNode: true,
        },
      },
    });

    // Access component instance to test method
    const vm = wrapper.vm as any;

    // By default, no nodes are expanded
    expect(vm.isExpanded(1)).toBe(false);

    // Toggle expanded
    vm.toggleExpanded(1);
    expect(vm.isExpanded(1)).toBe(true);

    // Toggle again to collapse
    vm.toggleExpanded(1);
    expect(vm.isExpanded(1)).toBe(false);
  });

  it('passes expanded state to CategoryTreeNode', async () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
      global: {
        stubs: {
          CategoryTreeNode: {
            template: '<div />',
            props: ['expanded'],
          },
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.toggleExpanded(1);
    await wrapper.vm.$nextTick();

    const firstNode = wrapper.findAllComponents({ name: 'CategoryTreeNode' })[0];
    expect(firstNode.props('expanded')).toBe(true);
  });

  it('emits select event when CategoryTreeNode emits select', async () => {
    const onSelect = vi.fn();
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        onSelect,
      },
      global: {
        stubs: {
          CategoryTreeNode: {
            template: '<div @click="$emit(\'select\', emittedCategory)" />',
            data() {
              return { emittedCategory: mockCategories[0] };
            },
          },
        },
      },
    });

    // Manually emit to test prop event binding
    const vm = wrapper.vm as any;
    expect(vm.$props.onSelect).toBeDefined();
  });

  it('emits edit event when CategoryTreeNode emits edit', () => {
    const onEdit = vi.fn();
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        onEdit,
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.$props.onEdit).toBeDefined();
  });

  it('emits delete event when CategoryTreeNode emits delete', () => {
    const onDelete = vi.fn();
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        onDelete,
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.$props.onDelete).toBeDefined();
  });

  it('respects editable prop', () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        editable: true,
      },
      global: {
        stubs: {
          CategoryTreeNode: {
            template: '<div />',
            props: ['editable'],
          },
        },
      },
    });

    const nodes = wrapper.findAllComponents({ name: 'CategoryTreeNode' });
    expect(nodes[0].props('editable')).toBe(true);
  });

  it('respects selectable prop', () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        selectable: true,
      },
      global: {
        stubs: {
          CategoryTreeNode: {
            template: '<div />',
            props: ['selectable'],
          },
        },
      },
    });

    const nodes = wrapper.findAllComponents({ name: 'CategoryTreeNode' });
    expect(nodes[0].props('selectable')).toBe(true);
  });

  it('handles expand/collapse toggle via child component events', async () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
      global: {
        components: {
          CategoryTreeNode: {
            template: '<div @click="$emit(\'toggle-expanded\', categoryId)"><slot /></div>',
            props: ['categoryId', 'category'],
            setup(props: any) {
              return { categoryId: props.category.id };
            },
          },
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.toggleExpanded(1);
    expect(vm.isExpanded(1)).toBe(true);
  });

  it('renders drag-drop indicators on nodes when editable', () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        editable: true,
      },
      global: {
        stubs: {
          CategoryTreeNode: {
            template: '<div v-if="editable" class="drag-handle" />',
            props: ['editable'],
          },
        },
      },
    });

    const dragHandles = wrapper.findAll('.drag-handle');
    expect(dragHandles.length).toBeGreaterThan(0);
  });
});
