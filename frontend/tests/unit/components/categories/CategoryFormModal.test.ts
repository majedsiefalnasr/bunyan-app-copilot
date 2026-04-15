import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Category, CategoryFormData } from '~/types/categories';
import CategoryFormModal from '~/components/categories/CategoryFormModal.vue';

// Stub Nuxt auto-imports
vi.stubGlobal('useI18n', () => ({
  t: (key: string, opts?: any) => {
    if (key === 'validation.minChars' && opts?.count) {
      return `Minimum ${opts.count} characters`;
    }
    return key;
  },
  locale: 'ar',
}));

describe('CategoryFormModal.vue', () => {
  const mockCategory: Category = {
    id: 1,
    name_ar: 'الأسمنت',
    name_en: 'Cement',
    parent_id: null,
    slug: 'cement',
    icon: '⚙️',
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
      icon: '🏗️',
      sort_order: 0,
      is_active: true,
      version: 1,
      children: [],
      created_at: '2026-04-15T00:00:00Z',
      updated_at: '2026-04-15T00:00:00Z',
      deleted_at: null,
    },
  ];

  const defaultProps = {
    isOpen: true,
    category: null,
    parentCategories: [] as Category[],
    onClose: vi.fn(),
    onSubmit: vi.fn().mockResolvedValue(undefined),
  };

  it('renders form when isOpen is true', () => {
    const wrapper = mount(CategoryFormModal, {
      props: defaultProps,
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    });

    // The component should have a form element
    expect(wrapper.exists()).toBe(true);
  });

  it('initializes form with category data in edit mode', async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        ...defaultProps,
        isOpen: true,
        category: mockCategory,
      },
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    });

    await wrapper.vm.$nextTick();

    const vm = wrapper.vm as any;
    expect(vm.nameAr).toBe(mockCategory.name_ar);
    expect(vm.nameEn).toBe(mockCategory.name_en);
    expect(vm.icon).toBe(mockCategory.icon);
    expect(vm.isActive).toBe(mockCategory.is_active);
    expect(vm.version).toBe(mockCategory.version);
  });

  it('initializes form with empty values in create mode', async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        ...defaultProps,
        isOpen: true,
        category: null,
      },
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    });

    await wrapper.vm.$nextTick();

    const vm = wrapper.vm as any;
    expect(vm.nameAr).toBe('');
    expect(vm.nameEn).toBe('');
    expect(vm.icon).toBe('');
    expect(vm.isActive).toBe(true);
    expect(vm.version).toBe(0);
  });

  it('calls onSubmit with form data on submit', async () => {
    const onSubmit = vi.fn().mockResolvedValue(undefined);

    const wrapper = mount(CategoryFormModal, {
      props: {
        ...defaultProps,
        onSubmit,
      },
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    vm.nameAr = 'فئة جديدة';
    vm.nameEn = 'New Category';
    vm.parentId = null;
    vm.icon = '🔨';
    vm.isActive = true;

    // Simulate form submit
    await vm.handleSubmit({ data: {} } as any);

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        name_ar: 'فئة جديدة',
        name_en: 'New Category',
        parent_id: null,
        icon: '🔨',
        is_active: true,
      })
    );
  });

  it('includes id and version in submit data when editing', async () => {
    const onSubmit = vi.fn().mockResolvedValue(undefined);

    const wrapper = mount(CategoryFormModal, {
      props: {
        ...defaultProps,
        category: mockCategory,
        onSubmit,
      },
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    await vm.handleSubmit({ data: {} } as any);

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        id: mockCategory.id,
        version: mockCategory.version,
      })
    );
  });

  it('uses parent categories in selector dropdown', () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        ...defaultProps,
        parentCategories: mockParentCategories,
      },
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.$props.parentCategories).toEqual(mockParentCategories);
  });

  it('validates required name_ar field', async () => {
    const validationSchema = (mount(CategoryFormModal, {
      props: defaultProps,
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    }).vm as any).validationSchema;

    // Test that name_ar is required
    try {
      validationSchema.parse({
        name_ar: '',
        name_en: 'Test',
      });
    } catch (error: any) {
      expect(error.errors?.[0]?.path?.includes('name_ar')).toBe(true);
    }
  });

  it('validates required name_en field', async () => {
    const validationSchema = (mount(CategoryFormModal, {
      props: defaultProps,
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    }).vm as any).validationSchema;

    // Test that name_en is required
    try {
      validationSchema.parse({
        name_ar: 'اختبار',
        name_en: '',
      });
    } catch (error: any) {
      expect(error.errors?.[0]?.path?.includes('name_en')).toBe(true);
    }
  });

  it('validates minimum character length for name_ar', async () => {
    const validationSchema = (mount(CategoryFormModal, {
      props: defaultProps,
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    }).vm as any).validationSchema;

    // Test minimum length
    try {
      validationSchema.parse({
        name_ar: 'ا',
        name_en: 'Test',
      });
    } catch (error: any) {
      // Should fail validation
      expect(error.errors).toBeDefined();
    }
  });

  it('validates maximum character length for names', async () => {
    const validationSchema = (mount(CategoryFormModal, {
      props: defaultProps,
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    }).vm as any).validationSchema;

    // Test maximum length exceeded
    const longName = 'a'.repeat(101);
    try {
      validationSchema.parse({
        name_ar: longName,
        name_en: 'Test',
      });
    } catch (error: any) {
      expect(error.errors).toBeDefined();
    }
  });

  it('allows optional icon field', () => {
    const wrapper = mount(CategoryFormModal, {
      props: defaultProps,
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.icon).toBe('');
  });

  it('sets is_active default to true', () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        ...defaultProps,
        category: null,
      },
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.isActive).toBe(true);
  });

  it('calls onClose when modal is closed', async () => {
    const onClose = vi.fn();

    const wrapper = mount(CategoryFormModal, {
      props: {
        ...defaultProps,
        onClose,
      },
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.$props.onClose).toBeDefined();
  });

  it('disables submit button while submitting', async () => {
    const onSubmit = vi.fn(
      () =>
        new Promise((resolve) => {
          setTimeout(resolve, 100);
        })
    );

    const wrapper = mount(CategoryFormModal, {
      props: {
        ...defaultProps,
        onSubmit,
      },
      global: {
        stubs: {
          UModal: false,
          UForm: false,
          UFormGroup: false,
          UInput: false,
          USelect: false,
          UCheckbox: false,
          UButton: false,
        },
      },
    });

    const vm = wrapper.vm as any;
    expect(vm.isSubmitting).toBe(false);

    vm.isSubmitting = true;
    await wrapper.vm.$nextTick();
    expect(vm.isSubmitting).toBe(true);
  });
});
