import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import CategoryTree from '../../../components/categories/CategoryTree.vue';

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: 'ar',
}));

const mockCategories = [
  {
    id: 1,
    parent_id: null,
    name_ar: 'مواد بناء',
    name_en: 'Building Materials',
    slug: 'building-materials',
    icon: 'icon-box',
    sort_order: 1,
    is_active: true,
    version: 0,
    created_at: '2026-04-15T10:00:00Z',
    updated_at: '2026-04-15T10:00:00Z',
    deleted_at: null,
    children: [],
  },
  {
    id: 3,
    parent_id: null,
    name_ar: 'كهرباء',
    name_en: 'Electrical',
    slug: 'electrical',
    icon: 'icon-zap',
    sort_order: 2,
    is_active: true,
    version: 0,
    created_at: '2026-04-15T10:00:02Z',
    updated_at: '2026-04-15T10:00:02Z',
    deleted_at: null,
    children: [],
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

describe('CategoryTree Component', () => {
  it('renders the empty state when no categories exist', () => {
    const wrapper = createWrapper({ categories: [] }, { CategoryTreeNode: true });

    expect(wrapper.text()).toContain('categories.noCategories');
  });

  it('renders one top-level list item per root category', () => {
    const wrapper = createWrapper({}, { CategoryTreeNode: true });

    expect(wrapper.findAll('li')).toHaveLength(2);
  });

  it('passes category, level, editable, and selectable props to nodes', () => {
    const wrapper = createWrapper(
      { editable: true, selectable: true },
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
    expect(nodes[0].props('level')).toBe(0);
    expect(nodes[0].props('editable')).toBe(true);
    expect(nodes[0].props('selectable')).toBe(true);
  });

  it('calls onSelect when a child node emits select', async () => {
    const onSelect = vi.fn();
    const wrapper = createWrapper(
      { onSelect },
      {
        CategoryTreeNode: {
          template:
            '<button data-testid="node" @click="$emit(\'select\', category)">{{ category.name_en }}</button>',
          props: ['category'],
          emits: ['select'],
        },
      }
    );

    await wrapper.get('[data-testid="node"]').trigger('click');

    expect(onSelect).toHaveBeenCalledWith(mockCategories[0]);
  });

  it('calls onEdit and onDelete when child nodes emit those actions', async () => {
    const onEdit = vi.fn();
    const onDelete = vi.fn();
    const wrapper = createWrapper(
      { onEdit, onDelete },
      {
        CategoryTreeNode: {
          template:
            '<div><button data-testid="edit" @click="$emit(\'edit\', category)" /><button data-testid="delete" @click="$emit(\'delete\', category)" /></div>',
          props: ['category'],
          emits: ['edit', 'delete'],
        },
      }
    );

    const buttons = wrapper.findAll('button');
    await buttons[0].trigger('click');
    await buttons[1].trigger('click');

    expect(onEdit).toHaveBeenCalledWith(mockCategories[0]);
    expect(onDelete).toHaveBeenCalledWith(mockCategories[0]);
  });

  it('updates expanded state when a child toggles expansion', async () => {
    const wrapper = createWrapper(
      {},
      {
        CategoryTreeNode: {
          name: 'CategoryTreeNode',
          template:
            '<button data-testid="toggle" @click="$emit(\'toggle-expanded\', category.id)" />',
          props: ['category', 'expanded'],
          emits: ['toggle-expanded'],
        },
      }
    );

    await wrapper.get('[data-testid="toggle"]').trigger('click');
    await wrapper.vm.$nextTick();

    const nodes = wrapper.findAllComponents({ name: 'CategoryTreeNode' });
    expect(nodes[0].props('expanded')).toBe(true);
  });
});
