import { describe, it, expect } from 'vitest';
import { useAuthSchemas } from '~/composables/useAuthSchemas';

describe('Auth Validation Schemas', () => {
  const {
    loginSchema,
    registerStep1Schema,
    registerStep2Schema,
    registerStep3Schema,
    registerStep4Schema,
    forgotPasswordSchema,
    resetPasswordSchema,
    verifyEmailSchema,
    profileSchema,
    changePasswordSchema,
  } = useAuthSchemas();

  describe('loginSchema', () => {
    it('requires valid email', () => {
      expect(() =>
        loginSchema.parse({
          email: 'invalid',
          password: 'ValidPass123!',
        })
      ).toThrow();
    });

    it('requires password', () => {
      expect(() =>
        loginSchema.parse({
          email: 'test@example.com',
          password: '',
        })
      ).toThrow();
    });

    it('accepts valid login data', () => {
      const valid = loginSchema.parse({
        email: 'test@example.com',
        password: 'ValidPass123!',
        rememberMe: true,
      });
      expect(valid.email).toBe('test@example.com');
    });
  });

  describe('registerStep1Schema (Role)', () => {
    it('requires role selection', () => {
      expect(() => registerStep1Schema.parse({ role: '' })).toThrow();
    });

    it('accepts customer role', () => {
      const valid = registerStep1Schema.parse({ role: 'customer' });
      expect(valid.role).toBe('customer');
    });

    it('accepts contractor role', () => {
      const valid = registerStep1Schema.parse({ role: 'contractor' });
      expect(valid.role).toBe('contractor');
    });
  });

  describe('registerStep2Schema (Personal Info)', () => {
    it('requires firstName and lastName', () => {
      expect(() =>
        registerStep2Schema.parse({
          firstName: '',
          lastName: 'Test',
          phone: '+966501234567',
          idNumber: '1234567890',
        })
      ).toThrow();
    });

    it('requires valid phone format', () => {
      expect(() =>
        registerStep2Schema.parse({
          firstName: 'Ahmed',
          lastName: 'Test',
          phone: 'invalid',
          idNumber: '1234567890',
        })
      ).toThrow();
    });

    it('accepts valid personal info', () => {
      const valid = registerStep2Schema.parse({
        firstName: 'Ahmed',
        lastName: 'Mohammed',
        phone: '+966501234567',
        idNumber: '1234567890',
      });
      expect(valid.firstName).toBe('Ahmed');
    });
  });

  describe('registerStep3Schema (Address)', () => {
    it('requires city selection', () => {
      expect(() =>
        registerStep3Schema.parse({
          city: '',
          district: 'test',
          address: 'Test Address',
        })
      ).toThrow();
    });

    it('requires address textarea', () => {
      expect(() =>
        registerStep3Schema.parse({
          city: 'riyadh',
          district: 'olaya',
          address: '',
        })
      ).toThrow();
    });

    it('accepts valid address data', () => {
      const valid = registerStep3Schema.parse({
        city: 'riyadh',
        district: 'olaya',
        address: '123 Main Street',
      });
      expect(valid.city).toBe('riyadh');
    });
  });

  describe('registerStep4Schema (Email & Password)', () => {
    it('requires valid email', () => {
      expect(() =>
        registerStep4Schema.parse({
          email: 'invalid',
          password: 'ValidPass123!',
          confirmPassword: 'ValidPass123!',
        })
      ).toThrow();
    });

    it('enforces password regex (8+ chars with upper, lower, number, special)', () => {
      expect(() =>
        registerStep4Schema.parse({
          email: 'test@example.com',
          password: 'weak',
          confirmPassword: 'weak',
        })
      ).toThrow();
    });

    it('requires matching passwords', () => {
      expect(() =>
        registerStep4Schema.parse({
          email: 'test@example.com',
          password: 'ValidPass123!',
          confirmPassword: 'DifferentPass123!',
        })
      ).toThrow();
    });

    it('accepts valid registration step 4 data', () => {
      const valid = registerStep4Schema.parse({
        email: 'test@example.com',
        password: 'ValidPass123!',
        confirmPassword: 'ValidPass123!',
      });
      expect(valid.email).toBe('test@example.com');
    });
  });

  describe('forgotPasswordSchema', () => {
    it('requires valid email', () => {
      expect(() => forgotPasswordSchema.parse({ email: 'invalid' })).toThrow();
    });

    it('accepts valid email', () => {
      const valid = forgotPasswordSchema.parse({ email: 'test@example.com' });
      expect(valid.email).toBe('test@example.com');
    });
  });

  describe('resetPasswordSchema', () => {
    it('requires valid password with regex rules', () => {
      expect(() =>
        resetPasswordSchema.parse({
          password: 'weak',
          confirmPassword: 'weak',
        })
      ).toThrow();
    });

    it('requires matching passwords', () => {
      expect(() =>
        resetPasswordSchema.parse({
          password: 'ValidPass123!',
          confirmPassword: 'DifferentPass123!',
        })
      ).toThrow();
    });

    it('accepts valid reset password data', () => {
      const valid = resetPasswordSchema.parse({
        password: 'ValidPass123!',
        confirmPassword: 'ValidPass123!',
      });
      expect(valid.password).toBe('ValidPass123!');
    });
  });

  describe('verifyEmailSchema', () => {
    it('requires exactly 6-digit code', () => {
      expect(() => verifyEmailSchema.parse({ code: '12345' })).toThrow();
    });

    it('accepts 6-digit code', () => {
      const valid = verifyEmailSchema.parse({ code: '123456' });
      expect(valid.code).toBe('123456');
    });
  });

  describe('profileSchema', () => {
    it('requires firstName and lastName', () => {
      expect(() =>
        profileSchema.parse({
          firstName: '',
          lastName: 'Test',
          phone: '+966501234567',
          city: 'riyadh',
          district: 'olaya',
          address: 'Test',
          languagePreference: 'ar',
        })
      ).toThrow();
    });

    it('accepts complete profile data', () => {
      const valid = profileSchema.parse({
        firstName: 'Ahmed',
        lastName: 'Mohammed',
        phone: '+966501234567',
        city: 'riyadh',
        district: 'olaya',
        address: 'Test Address',
        languagePreference: 'ar',
      });
      expect(valid.firstName).toBe('Ahmed');
    });
  });

  describe('changePasswordSchema', () => {
    it('requires all three fields', () => {
      expect(() =>
        changePasswordSchema.parse({
          currentPassword: 'OldPass123!',
          password: '',
          confirmPassword: '',
        })
      ).toThrow();
    });

    it('validates new password regex', () => {
      expect(() =>
        changePasswordSchema.parse({
          currentPassword: 'OldPass123!',
          password: 'weak',
          confirmPassword: 'weak',
        })
      ).toThrow();
    });

    it('accepts valid change password data', () => {
      const valid = changePasswordSchema.parse({
        currentPassword: 'OldPass123!',
        password: 'NewPass456!',
        confirmPassword: 'NewPass456!',
      });
      expect(valid.currentPassword).toBe('OldPass123!');
    });
  });
});
