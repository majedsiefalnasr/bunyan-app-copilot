import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CategoryTree from '~/components/categories/CategoryTree.vue';
import type { Category } from '~/types';

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: 'ar',
}));

const mockCategories: Category[] = [
  {
    id: 1,
    name_ar: 'المواد البناء',
    name_en: 'Building Materials',
    parent_id: null,
    slug: 'building-materials',
    icon: 'icon-materials',
    sort_order: 0,
    is_active: true,
    version: 1,
    children: [],
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
    icon: 'icon-tools',
    sort_order: 1,
    is_active: true,
    version: 1,
    children: [],
    created_at: '2026-04-15T00:00:00Z',
    updated_at: '2026-04-15T00:00:00Z',
    deleted_at: null,
  },
];

const createWrapper = (props: Record<string, unknown> = {}, stubs: Record<string, unknown> = {}) =>
  mount(CategoryTree, {
    props: {
      categories: mockCategories,
      ...props,
    },
    global: {
      stubs,
    },
  });

describe('CategoryTree.vue', () => {
  it('renders top-level categories as list items', () => {
    const wrapper = createWrapper({}, { CategoryTreeNode: true });

    expect(wrapper.findAll('li')).toHaveLength(2);
  });

  it('passes root categories into CategoryTreeNode stubs', () => {
    const wrapper = createWrapper(
      {},
      {
        CategoryTreeNode: {
          name: 'CategoryTreeNode',
          template: '<div />',
          props: ['category', 'level', 'editable', 'selectable', 'expanded'],
        },
      }
    );

    const nodes = wrapper.findAllComponents({ name: 'CategoryTreeNode' });

    expect(nodes).toHaveLength(2);
    expect(nodes[0].props('category')).toEqual(mockCategories[0]);
    expect(nodes[1].props('category')).toEqual(mockCategories[1]);
  });

  it('forwards selection callbacks to the provided handler', async () => {
    const onSelect = vi.fn();
    const wrapper = createWrapper(
      { onSelect },
      {
        CategoryTreeNode: {
          template:
            '<button data-testid="select-node" @click="$emit(\'select\', category)">{{ category.name_ar }}</button>',
          props: ['category'],
          emits: ['select'],
        },
      }
    );

    await wrapper.get('[data-testid="select-node"]').trigger('click');

    expect(onSelect).toHaveBeenCalledWith(mockCategories[0]);
  });

  it('forwards reorder and move callbacks', async () => {
    const onReorder = vi.fn();
    const onMove = vi.fn();
    const wrapper = createWrapper(
      { onReorder, onMove },
      {
        CategoryTreeNode: {
          template:
            '<div><button data-testid="reorder" @click="$emit(\'reorder\', category.id, 7)" /><button data-testid="move" @click="$emit(\'move\', category.id, null)" /></div>',
          props: ['category'],
          emits: ['reorder', 'move'],
        },
      }
    );

    const buttons = wrapper.findAll('button');
    await buttons[0].trigger('click');
    await buttons[1].trigger('click');

    expect(onReorder).toHaveBeenCalledWith(mockCategories[0].id, 7);
    expect(onMove).toHaveBeenCalledWith(mockCategories[0].id, null);
  });

  it('marks the toggled node as expanded on the next render', async () => {
    const wrapper = createWrapper(
      {},
      {
        CategoryTreeNode: {
          name: 'CategoryTreeNode',
          template:
            '<button data-testid="toggle-node" @click="$emit(\'toggle-expanded\', category.id)" />',
          props: ['category', 'expanded'],
          emits: ['toggle-expanded'],
        },
      }
    );

    await wrapper.get('[data-testid="toggle-node"]').trigger('click');
    await wrapper.vm.$nextTick();

    const nodes = wrapper.findAllComponents({ name: 'CategoryTreeNode' });
    expect(nodes[0].props('expanded')).toBe(true);
    expect(nodes[1].props('expanded')).toBe(false);
  });

  it('preserves RTL-friendly container attributes', () => {
    const wrapper = createWrapper({}, { CategoryTreeNode: true });

    expect(wrapper.find('.category-tree').attributes('dir')).toBe('auto');
  });
});
