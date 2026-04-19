import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import CategoryFormModal from '../../../components/categories/CategoryFormModal.vue';

vi.stubGlobal('useI18n', () => ({
  t: (key: string, opts?: { count?: number }) => {
    if (key === 'validation.minChars' && opts?.count) {
      return `Minimum ${opts.count} characters`;
    }

    return key;
  },
  locale: 'ar',
}));

const mockCategory = {
  id: 1,
  parent_id: null,
  name_ar: 'مواد بناء',
  name_en: 'Building Materials',
  slug: 'building-materials',
  icon: 'lucide-box',
  sort_order: 1,
  is_active: true,
  version: 3,
  created_at: '2026-04-15T10:00:00Z',
  updated_at: '2026-04-15T10:00:00Z',
  deleted_at: null,
  children: [],
};

const mockCategories = [
  mockCategory,
  {
    id: 2,
    parent_id: null,
    name_ar: 'كهرباء',
    name_en: 'Electrical',
    slug: 'electrical',
    icon: 'lucide-zap',
    sort_order: 2,
    is_active: true,
    version: 1,
    created_at: '2026-04-15T10:00:02Z',
    updated_at: '2026-04-15T10:00:02Z',
    deleted_at: null,
    children: [],
  },
];

const createWrapper = (overrides: Record<string, unknown> = {}) =>
  mount(CategoryFormModal, {
    props: {
      isOpen: true,
      category: null,
      parentCategories: mockCategories,
      onClose: vi.fn(),
      onSubmit: vi.fn().mockResolvedValue(undefined),
      ...overrides,
    },
    global: {
      stubs: {
        UModal: {
          props: ['modelValue'],
          template: '<div v-if="modelValue" data-testid="modal"><slot /></div>',
        },
        UCard: {
          template: '<div data-testid="card"><slot name="header" /><slot /></div>',
        },
        UForm: {
          props: ['state', 'schema'],
          emits: ['submit'],
          template:
            '<form data-testid="form" @submit.prevent="$emit(\'submit\', { data: state })"><slot /></form>',
        },
        UFormGroup: {
          props: ['label', 'name'],
          template: '<label :data-name="name"><span>{{ label }}</span><slot /></label>',
        },
        UInput: {
          props: ['modelValue'],
          emits: ['update:modelValue'],
          template:
            '<input data-testid="input" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
        },
        UButton: {
          props: ['type', 'loading', 'disabled'],
          emits: ['click'],
          template:
            '<button :type="type || \'button\'" :disabled="disabled" @click="$emit(\'click\')"><slot /></button>',
        },
        USelectMenu: {
          props: ['modelValue', 'options'],
          emits: ['update:modelValue'],
          template:
            '<select data-testid="parent-select" :value="modelValue ?? \'\'" @change="$emit(\'update:modelValue\', $event.target.value ? Number($event.target.value) : null)"><option value="">none</option><option v-for="option in options" :key="option.id" :value="option.id">{{ option.name_ar }}</option></select>',
        },
        UCheckbox: {
          props: ['modelValue', 'disabled'],
          emits: ['update:modelValue'],
          template:
            '<input data-testid="active-checkbox" type="checkbox" :checked="modelValue" :disabled="disabled" @change="$emit(\'update:modelValue\', $event.target.checked)" />',
        },
      },
    },
  });

describe('CategoryFormModal Component', () => {
  it('renders modal and form while open', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="modal"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="form"]').exists()).toBe(true);
  });

  it('does not render the modal content while closed', () => {
    const wrapper = createWrapper({ isOpen: false });

    expect(wrapper.find('[data-testid="modal"]').exists()).toBe(false);
  });

  it('initializes edit state from the provided category', () => {
    const wrapper = createWrapper({ category: mockCategory });
    const vm = wrapper.vm as unknown as {
      nameAr: string;
      nameEn: string;
      parentId: number | null;
      icon: string;
      isActive: boolean;
      version: number;
    };

    expect(vm.nameAr).toBe(mockCategory.name_ar);
    expect(vm.nameEn).toBe(mockCategory.name_en);
    expect(vm.parentId).toBe(mockCategory.parent_id);
    expect(vm.icon).toBe(mockCategory.icon);
    expect(vm.isActive).toBe(true);
    expect(vm.version).toBe(3);
  });

  it('submits create payload and closes the modal', async () => {
    const onSubmit = vi.fn().mockResolvedValue(undefined);
    const onClose = vi.fn();
    const wrapper = createWrapper({ onSubmit, onClose });
    const vm = wrapper.vm as unknown as {
      nameAr: string;
      nameEn: string;
      parentId: number | null;
      icon: string;
      isActive: boolean;
      handleSubmit: (event: unknown) => Promise<void>;
    };

    vm.nameAr = 'فئة جديدة';
    vm.nameEn = 'New Category';
    vm.parentId = 2;
    vm.icon = 'lucide-hammer';
    vm.isActive = false;

    await vm.handleSubmit({} as never);

    expect(onSubmit).toHaveBeenCalledWith({
      name_ar: 'فئة جديدة',
      name_en: 'New Category',
      parent_id: 2,
      icon: 'lucide-hammer',
      is_active: false,
    });
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('submits optimistic locking fields in edit mode', async () => {
    const onSubmit = vi.fn().mockResolvedValue(undefined);
    const wrapper = createWrapper({ category: mockCategory, onSubmit });
    const vm = wrapper.vm as unknown as {
      handleSubmit: (event: unknown) => Promise<void>;
    };

    await vm.handleSubmit({} as never);

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        id: mockCategory.id,
        version: mockCategory.version,
      })
    );
  });

  it('resets form state when closed', () => {
    const onClose = vi.fn();
    const wrapper = createWrapper({ onClose, category: mockCategory });
    const vm = wrapper.vm as unknown as {
      nameAr: string;
      nameEn: string;
      handleClose: () => void;
    };

    vm.nameAr = 'مؤقت';
    vm.nameEn = 'Temporary';
    vm.handleClose();

    expect(vm.nameAr).toBe(mockCategory.name_ar);
    expect(vm.nameEn).toBe(mockCategory.name_en);
    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('validates required and length constraints through the schema', () => {
    const wrapper = createWrapper();
    const vm = wrapper.vm as unknown as {
      validationSchema: {
        safeParse: (value: unknown) => {
          success: boolean;
          error?: { issues: Array<{ path: string[] }> };
        };
      };
    };

    const missingArabic = vm.validationSchema.safeParse({
      name_ar: '',
      name_en: 'Valid Name',
      parent_id: null,
      icon: '',
      is_active: true,
    });

    const shortEnglish = vm.validationSchema.safeParse({
      name_ar: 'اسم',
      name_en: 'A',
      parent_id: null,
      icon: '',
      is_active: true,
    });

    expect(missingArabic.success).toBe(false);
    expect(missingArabic.error?.issues[0]?.path).toContain('name_ar');
    expect(shortEnglish.success).toBe(false);
    expect(shortEnglish.error?.issues[0]?.path).toContain('name_en');
  });
});
