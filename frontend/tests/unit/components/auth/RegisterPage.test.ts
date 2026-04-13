import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import RegisterPage from '../../../../app/pages/auth/register.vue';

// ── Global stubs (Nuxt auto-imports) ────────────────────────────────

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('ar'),
}));

vi.stubGlobal('definePageMeta', vi.fn());

const cookieRef = { value: null as string | null };
vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));

const mockRegister = vi.fn();
const mockIsLoading = ref(false);
vi.stubGlobal('useAuth', () => ({
  register: mockRegister,
  isLoading: mockIsLoading,
  isAuthenticated: ref(false),
  user: ref(null),
  hasRole: vi.fn(),
}));

vi.stubGlobal('useToast', () => ({
  add: vi.fn(),
}));

// Stub Nuxt UI components
const UForm = {
  name: 'UForm',
  props: ['schema', 'state'],
  template: '<form @submit.prevent="$emit(\'submit\')"><slot /></form>',
};

const UFormField = {
  name: 'UFormField',
  props: ['label', 'name'],
  template: '<div class="form-field" :data-name="name"><slot /></div>',
};

const UInput = {
  name: 'UInput',
  props: ['modelValue', 'type', 'placeholder', 'icon', 'autocomplete', 'dir'],
  template: '<input :type="type || \'text\'" :placeholder="placeholder" :dir="dir" />',
};

const USelect = {
  name: 'USelect',
  props: ['modelValue', 'options', 'placeholder', 'valueKey'],
  template:
    '<select><option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option></select>',
};

const UButton = {
  name: 'UButton',
  props: ['type', 'block', 'loading', 'variant'],
  template: '<button :type="type || \'button\'"><slot /></button>',
};

const NuxtLink = {
  name: 'NuxtLink',
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
};

describe('Register Page', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    mockIsLoading.value = false;
  });

  function mountRegister() {
    return mount(RegisterPage, {
      global: {
        plugins: [createPinia()],
        stubs: {
          AuthCard: { props: ['title', 'subtitle'], template: '<div><slot /></div>' },
          UForm,
          UFormField,
          UInput,
          USelect,
          UButton,
          NuxtLink,
        },
      },
    });
  }

  it('renders all registration fields', () => {
    const wrapper = mountRegister();
    const fields = wrapper.findAll('.form-field');
    const fieldNames = fields.map((f) => f.attributes('data-name'));
    expect(fieldNames).toContain('name');
    expect(fieldNames).toContain('email');
    expect(fieldNames).toContain('phone');
    expect(fieldNames).toContain('password');
    expect(fieldNames).toContain('password_confirmation');
    expect(fieldNames).toContain('role');
  });

  it('renders role select with 4 options (no admin)', () => {
    const wrapper = mountRegister();
    const options = wrapper.findAll('option');
    expect(options).toHaveLength(4);

    const optionValues = options.map((o) => o.attributes('value'));
    expect(optionValues).toContain('customer');
    expect(optionValues).toContain('contractor');
    expect(optionValues).toContain('supervising_architect');
    expect(optionValues).toContain('field_engineer');
    expect(optionValues).not.toContain('admin');
  });

  it('phone field has dir="ltr" for number input', () => {
    const wrapper = mountRegister();
    const phoneInput = wrapper.find('input[dir="ltr"]');
    expect(phoneInput.exists()).toBe(true);
  });

  it('password fields have type="password"', () => {
    const wrapper = mountRegister();
    const passwordInputs = wrapper.findAll('input[type="password"]');
    expect(passwordInputs).toHaveLength(2);
  });

  it('renders login link for existing users', () => {
    const wrapper = mountRegister();
    const links = wrapper.findAll('a');
    const loginLink = links.find((l) => l.attributes('href')?.includes('login'));
    expect(loginLink).toBeTruthy();
  });
});
