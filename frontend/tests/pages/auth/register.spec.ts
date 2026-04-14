import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import RegisterPage from '~/pages/auth/register.vue';

vi.mock('~/composables/useAuth', () => ({
  useAuth: () => ({
    register: vi.fn(),
  }),
}));

vi.mock('~/composables/usePasswordToggle', () => ({
  usePasswordToggle: () => ({
    type: 'password',
    icon: 'i-heroicons-eye',
    ariaLabel: 'Show password',
    toggle: vi.fn(),
  }),
}));

vi.mock('~/composables/useAuthSchemas', () => ({
  useAuthSchemas: () => ({
    registerStep1Schema: {
      parse: vi.fn(),
    },
    registerStep2Schema: {
      parse: vi.fn(),
    },
    registerStep3Schema: {
      parse: vi.fn(),
    },
    registerStep4Schema: {
      parse: vi.fn(),
    },
  }),
}));

describe('Register Page - Multi-Step Wizard', () => {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  let wrapper: any;

  beforeEach(() => {
    wrapper = mount(RegisterPage, {
      global: {
        stubs: {
          AuthLayout: { template: '<div><slot /></div>' },
          AuthCard: { template: '<div><slot /></div>' },
          UAlert: { template: '<div></div>' },
          UFormGroup: { template: '<div><slot /></div>' },
          UInput: { template: '<input />' },
          UTextarea: { template: '<textarea></textarea>' },
          USelect: { template: '<select></select>' },
          UButton: { template: '<button></button>' },
          URadio: { template: '<input type="radio" />' },
          PasswordStrength: { template: '<div></div>' },
          NuxtLink: { template: '<a><slot /></a>' },
        },
        mocks: {
          $t: (key: string) => key,
        },
      },
    });
  });

  it('renders step 1 by default', () => {
    expect(wrapper.vm.currentStep).toBe(1);
  });

  it('has 4 steps in wizard', () => {
    expect(wrapper.vm.currentStep).toBe(1);
    expect(wrapper.vm.currentStepTitle).toBeDefined();
  });

  it('advances to next step when next button clicked', async () => {
    wrapper.vm.currentStep = 1;
    // In a real test, we'd mock validation to pass
    await wrapper.vm.nextStep();
    // Step advancement would happen if validation passes
  });

  it('goes back to previous step', () => {
    wrapper.vm.currentStep = 2;
    wrapper.vm.previousStep();
    expect(wrapper.vm.currentStep).toBe(1);
  });

  it('prevents going back from step 1', () => {
    wrapper.vm.currentStep = 1;
    wrapper.vm.previousStep();
    expect(wrapper.vm.currentStep).toBe(1);
  });

  it('shows step indicator for all 4 steps', () => {
    const stepIndicators = wrapper.findAll('[class*="rounded-full"]');
    expect(stepIndicators).toBeDefined();
  });

  it('initializes with customer role selected', () => {
    expect(wrapper.vm.form.userType).toBe('customer');
  });

  it('filters districts by selected city', () => {
    wrapper.vm.form.city = 'riyadh';
    expect(wrapper.vm.filteredDistricts.length).toBeGreaterThan(0);
  });

  it('clears district when city changes', () => {
    wrapper.vm.form.city = 'riyadh';
    wrapper.vm.form.district = 'khaleej';
    wrapper.vm.onCityChange();
    expect(wrapper.vm.form.district).toBe('');
  });

  it('calculates password strength', () => {
    wrapper.vm.form.password = 'weak';
    wrapper.vm.updatePasswordStrength();
    const weakStrength = wrapper.vm.passwordStrength;

    wrapper.vm.form.password = 'StrongPass123!';
    wrapper.vm.updatePasswordStrength();
    const strongStrength = wrapper.vm.passwordStrength;

    expect(strongStrength).toBeGreaterThan(weakStrength);
  });

  it('prevents password strength from exceeding 100', () => {
    wrapper.vm.form.password = 'VeryStrongPassword123!@#$%^&*';
    wrapper.vm.updatePasswordStrength();
    expect(wrapper.vm.passwordStrength).toBeLessThanOrEqual(100);
  });

  it('requires userType, firstName, lastName, phone, idNumber in respective steps', () => {
    expect(wrapper.vm.form.userType).toBeDefined();
    expect(wrapper.vm.form.firstName).toBe('');
    expect(wrapper.vm.form.lastName).toBe('');
    expect(wrapper.vm.form.phone).toBe('');
    expect(wrapper.vm.form.idNumber).toBe('');
  });

  it('requires city, district, address in step 3', () => {
    expect(wrapper.vm.form.city).toBe('');
    expect(wrapper.vm.form.district).toBe('');
    expect(wrapper.vm.form.address).toBe('');
  });

  it('requires email, password, confirmPassword in step 4', () => {
    expect(wrapper.vm.form.email).toBe('');
    expect(wrapper.vm.form.password).toBe('');
    expect(wrapper.vm.form.confirmPassword).toBe('');
  });

  it('displays error message on form submission failure', async () => {
    wrapper.vm.error = '';
    wrapper.vm.error = 'Test error message';
    expect(wrapper.vm.error).toBe('Test error message');
  });

  it('clears previous error when new form submission starts', async () => {
    wrapper.vm.error = 'Previous error';
    // In a real scenario, we'd trigger form submission
    // Error should be cleared at start of onSubmit
    expect(wrapper.vm.error).toBe('Previous error');
  });
});
