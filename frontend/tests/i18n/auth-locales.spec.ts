import { describe, it, expect } from 'vitest';
import ar from '~/locales/ar.json';
import en from '~/locales/en.json';

describe('i18n Auth Locales Coverage', () => {
  const requiredAuthKeys = [
    // Login
    'auth.login.title',
    'auth.login.subtitle',
    'auth.login.email',
    'auth.login.password',
    'auth.login.remember_me',
    'auth.login.submit',
    'auth.login.forgot_password',
    'auth.login.no_account',

    // Register
    'auth.register.title',
    'auth.register.subtitle',
    'auth.register.step_1_title',
    'auth.register.step_1_customer',
    'auth.register.step_1_contractor',
    'auth.register.step_2_title',
    'auth.register.step_2_first_name',
    'auth.register.step_2_last_name',
    'auth.register.step_2_phone',
    'auth.register.step_2_id_number',
    'auth.register.step_3_title',
    'auth.register.step_3_city',
    'auth.register.step_3_district',
    'auth.register.step_3_address',
    'auth.register.step_4_title',
    'auth.register.step_4_email',
    'auth.register.step_4_password',
    'auth.register.step_4_confirm_password',
    'auth.register.previous',
    'auth.register.next',
    'auth.register.submit',
    'auth.register.have_account',

    // Forgot Password
    'auth.forgot_password.title',
    'auth.forgot_password.subtitle',
    'auth.forgot_password.email',
    'auth.forgot_password.submit',
    'auth.forgot_password.success_message',
    'auth.forgot_password.back_to_login',

    // Reset Password
    'auth.reset_password.title',
    'auth.reset_password.subtitle',
    'auth.reset_password.password',
    'auth.reset_password.confirm_password',
    'auth.reset_password.submit',
    'auth.reset_password.token_expired',
    'auth.reset_password.back_to_login',

    // Verify Email
    'auth.verify_email.title',
    'auth.verify_email.subtitle',
    'auth.verify_email.message',
    'auth.verify_email.code_label',
    'auth.verify_email.submit',
    'auth.verify_email.resend',
    'auth.verify_email.resend_countdown',
    'auth.verify_email.change_email',

    // Profile
    'auth.profile.title',
    'auth.profile.avatar',
    'auth.profile.upload_avatar',
    'auth.profile.first_name',
    'auth.profile.last_name',
    'auth.profile.phone',
    'auth.profile.city',
    'auth.profile.district',
    'auth.profile.address',
    'auth.profile.language',
    'auth.profile.save',
    'auth.profile.cancel',
    'auth.profile.change_password',

    // Change Password
    'auth.change_password.title',
    'auth.change_password.current_password',
    'auth.change_password.password',
    'auth.change_password.password_confirmation',
    'auth.change_password.submit',
    'auth.change_password.cancel',

    // Password Strength
    'auth.password_strength.weak',
    'auth.password_strength.fair',
    'auth.password_strength.good',
    'auth.password_strength.strong',

    // Messages
    'auth.logout.button',
  ];

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const getNestedValue = (obj: any, path: string): any => {
    return path.split('.').reduce((current, prop) => current?.[prop], obj);
  };

  describe('Arabic Locales', () => {
    it('has auth section', () => {
      expect(ar.auth).toBeDefined();
    });

    requiredAuthKeys.forEach((key) => {
      it(`has key: ${key}`, () => {
        expect(getNestedValue(ar, key)).toBeDefined();
        expect(typeof getNestedValue(ar, key)).toBe('string');
        expect(getNestedValue(ar, key).length).toBeGreaterThan(0);
      });
    });
  });

  describe('English Locales', () => {
    it('has auth section', () => {
      expect(en.auth).toBeDefined();
    });

    requiredAuthKeys.forEach((key) => {
      it(`has key: ${key}`, () => {
        expect(getNestedValue(en, key)).toBeDefined();
        expect(typeof getNestedValue(en, key)).toBe('string');
        expect(getNestedValue(en, key).length).toBeGreaterThan(0);
      });
    });
  });

  describe('Locale Parity', () => {
    it('same number of keys in ar.json and en.json', () => {
      const arKeys = Object.keys(ar.auth || {}).length;
      const enKeys = Object.keys(en.auth || {}).length;
      expect(arKeys).toBe(enKeys);
    });

    requiredAuthKeys.forEach((key) => {
      it(`both locales have ${key}`, () => {
        expect(getNestedValue(ar, key)).toBeDefined();
        expect(getNestedValue(en, key)).toBeDefined();
      });
    });
  });

  describe('RTL Markers', () => {
    it('Arabic text uses Arabic characters', () => {
      const arTitle = getNestedValue(ar, 'auth.login.title');
      expect(/[\u0600-\u06FF]/.test(arTitle)).toBe(true);
    });

    it('English text uses Latin characters', () => {
      const enTitle = getNestedValue(en, 'auth.login.title');
      expect(/[a-zA-Z]/.test(enTitle)).toBe(true);
    });
  });
});
