import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import CategoryFormModal from '~/components/categories/CategoryFormModal.vue';
import type { Category } from '~/types';

vi.stubGlobal('useI18n', () => ({
  t: (key: string, opts?: { count?: number }) => {
    if (key === 'validation.minChars' && opts?.count) {
      return `Minimum ${opts.count} characters`;
    }

    return key;
  },
  locale: 'ar',
}));

const mockCategory: Category = {
  id: 1,
  name_ar: 'الأسمنت',
  name_en: 'Cement',
  parent_id: null,
  slug: 'cement',
  icon: 'icon-cement',
  sort_order: 0,
  is_active: true,
  version: 2,
  children: [],
  created_at: '2026-04-15T00:00:00Z',
  updated_at: '2026-04-15T00:00:00Z',
  deleted_at: null,
};

const mockParentCategories: Category[] = [
  {
    id: 2,
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
];

const createWrapper = (props: Record<string, unknown> = {}) =>
  mount(CategoryFormModal, {
    props: {
      isOpen: true,
      category: null,
      parentCategories: mockParentCategories,
      onClose: vi.fn(),
      onSubmit: vi.fn().mockResolvedValue(undefined),
      ...props,
    },
    global: {
      stubs: {
        UModal: {
          props: ['modelValue'],
          template: '<div v-if="modelValue"><slot /></div>',
        },
        UCard: {
          template: '<section><slot name="header" /><slot /></section>',
        },
        UForm: {
          props: ['state'],
          emits: ['submit'],
          template: '<form @submit.prevent="$emit(\'submit\', { data: state })"><slot /></form>',
        },
        UFormGroup: {
          props: ['label'],
          template: '<div><span>{{ label }}</span><slot /></div>',
        },
        UInput: {
          props: ['modelValue'],
          emits: ['update:modelValue'],
          template:
            '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
        },
        USelectMenu: {
          props: ['options', 'modelValue'],
          emits: ['update:modelValue'],
          template:
            '<select :value="modelValue ?? \'\'" @change="$emit(\'update:modelValue\', $event.target.value ? Number($event.target.value) : null)"><option value="">none</option><option v-for="option in options" :key="option.id" :value="option.id">{{ option.name_ar }}</option></select>',
        },
        UCheckbox: {
          props: ['modelValue'],
          emits: ['update:modelValue'],
          template:
            '<input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" />',
        },
        UButton: {
          props: ['disabled', 'loading', 'type'],
          emits: ['click'],
          template:
            '<button :type="type || \'button\'" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>',
        },
      },
    },
  });

describe('CategoryFormModal.vue', () => {
  it('starts with empty create-mode defaults', () => {
    const wrapper = createWrapper();
    const vm = wrapper.vm as unknown as {
      nameAr: string;
      nameEn: string;
      parentId: number | null;
      icon: string;
      isActive: boolean;
      version: number;
    };

    expect(vm.nameAr).toBe('');
    expect(vm.nameEn).toBe('');
    expect(vm.parentId).toBeNull();
    expect(vm.icon).toBe('');
    expect(vm.isActive).toBe(true);
    expect(vm.version).toBe(0);
  });

  it('hydrates edit-mode state from category props', () => {
    const wrapper = createWrapper({ category: mockCategory });
    const vm = wrapper.vm as unknown as {
      nameAr: string;
      nameEn: string;
      icon: string;
      version: number;
    };

    expect(vm.nameAr).toBe(mockCategory.name_ar);
    expect(vm.nameEn).toBe(mockCategory.name_en);
    expect(vm.icon).toBe(mockCategory.icon ?? '');
    expect(vm.version).toBe(mockCategory.version);
  });

  it('exposes parent categories to the select input', () => {
    const wrapper = createWrapper();
    const select = wrapper.find('select');

    expect(select.exists()).toBe(true);
    expect(select.text()).toContain(mockParentCategories[0].name_ar);
  });

  it('calls onSubmit with edit payload and then closes', async () => {
    const onSubmit = vi.fn().mockResolvedValue(undefined);
    const onClose = vi.fn();
    const wrapper = createWrapper({ category: mockCategory, onSubmit, onClose });
    const vm = wrapper.vm as unknown as {
      handleSubmit: (event: unknown) => Promise<void>;
    };

    await vm.handleSubmit({} as never);

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        id: mockCategory.id,
        version: mockCategory.version,
        name_ar: mockCategory.name_ar,
        name_en: mockCategory.name_en,
      })
    );
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('keeps isSubmitting accurate across a failed submission', async () => {
    const onSubmit = vi.fn().mockRejectedValue(new Error('submit failed'));
    const wrapper = createWrapper({ onSubmit });
    const vm = wrapper.vm as unknown as {
      isSubmitting: boolean;
      handleSubmit: (event: unknown) => Promise<void>;
    };

    await vm.handleSubmit({} as never);

    expect(vm.isSubmitting).toBe(false);
  });

  it('rejects invalid schemas through zod', () => {
    const wrapper = createWrapper();
    const vm = wrapper.vm as unknown as {
      validationSchema: {
        safeParse: (value: unknown) => { success: boolean };
      };
    };

    expect(
      vm.validationSchema.safeParse({
        name_ar: 'ا',
        name_en: 'C',
        parent_id: null,
        icon: '',
        is_active: true,
      }).success
    ).toBe(false);

    expect(
      vm.validationSchema.safeParse({
        name_ar: 'اسم صالح',
        name_en: 'Valid Name',
        parent_id: null,
        icon: 'x'.repeat(101),
        is_active: true,
      }).success
    ).toBe(false);
  });
});
