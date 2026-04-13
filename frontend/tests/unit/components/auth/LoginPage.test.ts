import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import LoginPage from '../../../../app/pages/auth/login.vue';

// ── Global stubs (Nuxt auto-imports) ────────────────────────────────

vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('ar'),
}));

vi.stubGlobal('definePageMeta', vi.fn());

const cookieRef = { value: null as string | null };
vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));

const mockLogin = vi.fn();
const mockIsLoading = ref(false);
vi.stubGlobal('useAuth', () => ({
  login: mockLogin,
  isLoading: mockIsLoading,
  isAuthenticated: ref(false),
  user: ref(null),
  hasRole: vi.fn(),
}));

vi.stubGlobal('useToast', () => ({
  add: vi.fn(),
}));

// Mock AuthCard as a simple pass-through
vi.stubGlobal('AuthCard', {
  props: ['title', 'subtitle'],
  template: '<div class="auth-card"><slot /></div>',
});

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
  template:
    '<input :type="type || \'text\'" :placeholder="placeholder" :value="modelValue" :dir="dir" @input="$emit(\'update:modelValue\', $event.target.value)" />',
};

const UButton = {
  name: 'UButton',
  props: ['type', 'block', 'loading', 'variant'],
  template: '<button :type="type || \'button\'" :disabled="loading"><slot /></button>',
};

const NuxtLink = {
  name: 'NuxtLink',
  props: ['to'],
  template: '<a :href="to"><slot /></a>',
};

describe('Login Page', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    mockIsLoading.value = false;
  });

  function mountLogin() {
    return mount(LoginPage, {
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

  it('renders email and password fields', () => {
    const wrapper = mountLogin();
    const fields = wrapper.findAll('.form-field');
    const fieldNames = fields.map((f) => f.attributes('data-name'));
    expect(fieldNames).toContain('email');
    expect(fieldNames).toContain('password');
  });

  it('renders submit button', () => {
    const wrapper = mountLogin();
    const submitBtn = wrapper.find('button[type="submit"]');
    expect(submitBtn.exists()).toBe(true);
  });

  it('renders forgot password link', () => {
    const wrapper = mountLogin();
    const links = wrapper.findAll('a');
    const forgotLink = links.find((l) => l.attributes('href')?.includes('forgot-password'));
    expect(forgotLink).toBeTruthy();
  });

  it('renders register link', () => {
    const wrapper = mountLogin();
    const links = wrapper.findAll('a');
    const registerLink = links.find((l) => l.attributes('href')?.includes('register'));
    expect(registerLink).toBeTruthy();
  });

  it('password field has type="password"', () => {
    const wrapper = mountLogin();
    const passwordInput = wrapper.find('input[type="password"]');
    expect(passwordInput.exists()).toBe(true);
  });
});
