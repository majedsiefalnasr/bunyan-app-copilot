import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import ForgotPasswordPage from '../../../../app/pages/auth/forgot-password.vue';

// ── Global stubs (Nuxt auto-imports) ────────────────────────────────

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('ar'),
}));

vi.stubGlobal('definePageMeta', vi.fn());

const cookieRef = { value: null as string | null };
vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));

const mockForgotPassword = vi.fn();
const mockIsLoading = ref(false);
vi.stubGlobal('useAuth', () => ({
  forgotPassword: mockForgotPassword,
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
  props: ['modelValue', 'type', 'placeholder', 'icon', 'autocomplete'],
  template: '<input :type="type || \'text\'" :placeholder="placeholder" />',
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

describe('Forgot Password Page', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    mockIsLoading.value = false;
  });

  function mountForgotPassword() {
    return mount(ForgotPasswordPage, {
      global: {
        plugins: [createPinia()],
        stubs: {
          AuthCard: { props: ['title', 'subtitle'], template: '<div><slot /></div>' },
          UForm,
          UFormField,
          UInput,
          UButton,
          NuxtLink,
        },
      },
    });
  }

  it('renders email field', () => {
    const wrapper = mountForgotPassword();
    const fields = wrapper.findAll('.form-field');
    const fieldNames = fields.map((f) => f.attributes('data-name'));
    expect(fieldNames).toContain('email');
  });

  it('renders submit button', () => {
    const wrapper = mountForgotPassword();
    const submitBtn = wrapper.find('button[type="submit"]');
    expect(submitBtn.exists()).toBe(true);
  });

  it('renders back to login link', () => {
    const wrapper = mountForgotPassword();
    const links = wrapper.findAll('a');
    const loginLink = links.find((l) => l.attributes('href')?.includes('login'));
    expect(loginLink).toBeTruthy();
  });
});
