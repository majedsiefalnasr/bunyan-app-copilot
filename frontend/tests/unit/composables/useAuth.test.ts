import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { readonly as vueReadonly, ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia';

import { useAuth } from '../../../composables/useAuth';
import { useAuthStore } from '../../../stores/auth';
vi.stubGlobal('readonly', vueReadonly);

// Mock vue-i18n — explicitly imported by useAuth.ts
vi.mock('vue-i18n', () => ({
  useI18n: () => ({ locale: ref('ar') }),
}));

// useApi spy — captured here so we can configure per-test response
const mockApiFetch = vi.fn();
vi.mock('../../../composables/useApi', () => ({
  useApi: () => ({ apiFetch: mockApiFetch }),
}));

describe('useAuth composable', () => {
  let cookieRef: ReturnType<typeof ref<string | null>>;

  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();

    // Stub useCookie BEFORE useAuthStore() is called — store factory runs on first use
    cookieRef = ref<string | null>(null);
    vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));
  });

  afterEach(() => {
    vi.clearAllMocks();
    // Re-stub globals that may have been cleared by clearAllMocks
    vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));
  });

  // ── Authentication state ────────────────────────────────────────────

  it('isAuthenticated is false when no user and no token', () => {
    const { isAuthenticated } = useAuth();
    expect(isAuthenticated.value).toBe(false);
  });

  it('isAuthenticated is false when token exists but user is null', () => {
    cookieRef.value = 'some-token';
    const { isAuthenticated } = useAuth();
    expect(isAuthenticated.value).toBe(false);
  });

  it('isAuthenticated is true when token and user are both set', () => {
    cookieRef.value = 'some-token';
    const { isAuthenticated } = useAuth();
    const store = useAuthStore();
    store.setUser({ id: 1, name: 'Alice', email: 'a@b.com', role: 'customer' } as never);
    expect(isAuthenticated.value).toBe(true);
  });

  // ── hasRole ─────────────────────────────────────────────────────────

  it('hasRole returns false when no user', () => {
    const { hasRole } = useAuth();
    expect(hasRole('customer')).toBe(false);
  });

  it('hasRole returns true when user role matches', () => {
    const { hasRole } = useAuth();
    const store = useAuthStore();
    store.setUser({ id: 1, name: 'Bob', email: 'b@c.com', role: 'contractor' } as never);
    expect(hasRole('contractor')).toBe(true);
    expect(hasRole('customer')).toBe(false);
  });

  it('hasRole accepts an array of roles', () => {
    const { hasRole } = useAuth();
    const store = useAuthStore();
    store.setUser({ id: 2, name: 'Carol', email: 'c@d.com', role: 'admin' } as never);
    expect(hasRole(['admin', 'customer'])).toBe(true);
    expect(hasRole(['contractor', 'field_engineer'])).toBe(false);
  });

  // ── login ───────────────────────────────────────────────────────────

  it('login calls POST /api/v1/auth/login and stores token + user', async () => {
    const mockUser = { id: 1, name: 'Test', email: 'test@example.com', role: 'customer' };
    mockApiFetch.mockResolvedValueOnce({
      success: true,
      data: { user: mockUser, token: 'new-token' },
    });

    const { login } = useAuth();
    const result = await login('test@example.com', 'password123');

    expect(mockApiFetch).toHaveBeenCalledWith('/api/v1/auth/login', {
      method: 'POST',
      body: { email: 'test@example.com', password: 'password123' },
    });
    expect(result).toEqual(mockUser);
    expect(cookieRef.value).toBe('new-token');
    const store = useAuthStore();
    expect(store.user).toEqual(mockUser);
  });

  it('login sets isLoading during request', async () => {
    const store = useAuthStore();
    let loadingDuringCall = false;
    mockApiFetch.mockImplementationOnce(() => {
      loadingDuringCall = store.isLoading;
      return Promise.resolve({ success: true, data: { user: { id: 1 }, token: 'tok' } });
    });

    const { login } = useAuth();
    await login('a@b.com', 'pass');

    expect(loadingDuringCall).toBe(true);
    expect(store.isLoading).toBe(false);
  });

  it('login resets isLoading on error', async () => {
    mockApiFetch.mockRejectedValueOnce(new Error('Invalid credentials'));
    const { login } = useAuth();
    await expect(login('a@b.com', 'wrong')).rejects.toThrow('Invalid credentials');
    const store = useAuthStore();
    expect(store.isLoading).toBe(false);
  });

  // ── register ────────────────────────────────────────────────────────

  it('register calls POST /api/v1/auth/register and stores token + user', async () => {
    const mockUser = { id: 2, name: 'New User', email: 'new@example.com', role: 'contractor' };
    mockApiFetch.mockResolvedValueOnce({
      success: true,
      data: { user: mockUser, token: 'reg-token' },
    });

    const registerData = {
      firstName: 'New',
      lastName: 'User',
      email: 'new@example.com',
      phone: '0512345678',
      password: 'password123',
      password_confirmation: 'password123',
      role: 'contractor',
    };

    const { register } = useAuth();
    const result = await register(registerData);

    expect(mockApiFetch).toHaveBeenCalledWith('/api/v1/auth/register', {
      method: 'POST',
      body: {
        name: 'New User',
        email: 'new@example.com',
        phone: '0512345678',
        password: 'password123',
        password_confirmation: 'password123',
        role: 'contractor',
      },
    });
    expect(result).toEqual(mockUser);
    expect(cookieRef.value).toBe('reg-token');
  });

  it('register resets isLoading on error', async () => {
    mockApiFetch.mockRejectedValueOnce(new Error('Validation error'));
    const { register } = useAuth();
    await expect(
      register({
        firstName: 'X',
        lastName: 'Y',
        email: 'x@x.com',
        phone: '0500000000',
        password: 'p',
        password_confirmation: 'p',
        role: 'customer',
      })
    ).rejects.toThrow();
    const store = useAuthStore();
    expect(store.isLoading).toBe(false);
  });

  // ── logout ──────────────────────────────────────────────────────────

  it('logout calls POST /api/v1/auth/logout', async () => {
    mockApiFetch.mockResolvedValueOnce({});
    const { logout } = useAuth();
    await logout();
    expect(mockApiFetch).toHaveBeenCalledWith('/api/v1/auth/logout', { method: 'POST' });
  });

  it('logout clears auth state even when API call fails', async () => {
    mockApiFetch.mockRejectedValueOnce(new Error('Network error'));
    cookieRef.value = 'some-token';
    const store = useAuthStore();
    store.setUser({ id: 1, name: 'Dan', email: 'd@e.com', role: 'customer' } as never);

    const { logout } = useAuth();
    await logout();

    // Cookie ref and user should be cleared regardless of API failure
    expect(cookieRef.value).toBeNull();
    expect(store.user).toBeNull();
  });

  it('logout navigates to login page with locale prefix', async () => {
    mockApiFetch.mockResolvedValueOnce({});
    const { logout } = useAuth();
    await logout();
    expect(navigateTo).toHaveBeenCalledWith('/ar/auth/login');
  });

  // ── forgotPassword ──────────────────────────────────────────────────

  it('forgotPassword calls POST /api/v1/auth/forgot-password', async () => {
    mockApiFetch.mockResolvedValueOnce({ success: true });
    const { forgotPassword } = useAuth();
    await forgotPassword('user@example.com');

    expect(mockApiFetch).toHaveBeenCalledWith('/api/v1/auth/forgot-password', {
      method: 'POST',
      body: { email: 'user@example.com' },
    });
  });

  it('forgotPassword resets isLoading on completion', async () => {
    mockApiFetch.mockResolvedValueOnce({ success: true });
    const { forgotPassword } = useAuth();
    await forgotPassword('user@example.com');
    const store = useAuthStore();
    expect(store.isLoading).toBe(false);
  });

  it('forgotPassword resets isLoading on error', async () => {
    mockApiFetch.mockRejectedValueOnce(new Error('Rate limit'));
    const { forgotPassword } = useAuth();
    await expect(forgotPassword('user@example.com')).rejects.toThrow();
    const store = useAuthStore();
    expect(store.isLoading).toBe(false);
  });

  // ── resetPassword ───────────────────────────────────────────────────

  it('resetPassword calls POST /api/v1/auth/reset-password', async () => {
    mockApiFetch.mockResolvedValueOnce({ success: true });
    const resetData = {
      email: 'user@example.com',
      token: 'reset-token-xyz',
      password: 'newpassword123',
      password_confirmation: 'newpassword123',
    };
    const { resetPassword } = useAuth();
    await resetPassword(resetData);

    expect(mockApiFetch).toHaveBeenCalledWith('/api/v1/auth/reset-password', {
      method: 'POST',
      body: resetData,
    });
  });

  it('resetPassword resets isLoading on error', async () => {
    mockApiFetch.mockRejectedValueOnce(new Error('Invalid token'));
    const { resetPassword } = useAuth();
    await expect(
      resetPassword({
        email: 'a@b.com',
        token: 'bad',
        password: 'x',
        password_confirmation: 'x',
      })
    ).rejects.toThrow();
    const store = useAuthStore();
    expect(store.isLoading).toBe(false);
  });

  // ── resendVerification ──────────────────────────────────────────────

  it('resendVerification calls POST /api/v1/auth/email/resend', async () => {
    mockApiFetch.mockResolvedValueOnce({ success: true });
    const { resendVerification } = useAuth();
    await resendVerification();

    expect(mockApiFetch).toHaveBeenCalledWith('/api/v1/auth/email/resend', {
      method: 'POST',
    });
  });

  it('resendVerification resets isLoading on error', async () => {
    mockApiFetch.mockRejectedValueOnce(new Error('Rate limited'));
    const { resendVerification } = useAuth();
    await expect(resendVerification()).rejects.toThrow();
    const store = useAuthStore();
    expect(store.isLoading).toBe(false);
  });

  // ── fetchCurrentUser ─────────────────────────────────────────────────

  it('fetchCurrentUser calls GET /api/v1/auth/user', async () => {
    const userData = { id: 5, name: 'Eve', email: 'e@f.com', role: 'customer' };
    mockApiFetch.mockResolvedValueOnce({ success: true, data: userData });

    const { fetchCurrentUser } = useAuth();
    await fetchCurrentUser();

    expect(mockApiFetch).toHaveBeenCalledWith('/api/v1/auth/user');
  });

  it('fetchCurrentUser sets user in store on success', async () => {
    const userData = { id: 5, name: 'Eve', email: 'e@f.com', role: 'customer' };
    mockApiFetch.mockResolvedValueOnce({ success: true, data: userData });

    const { fetchCurrentUser } = useAuth();
    const result = await fetchCurrentUser();

    expect(result).toEqual(userData);
    const store = useAuthStore();
    expect(store.user).toEqual(userData);
  });

  it('fetchCurrentUser returns null on API failure', async () => {
    mockApiFetch.mockRejectedValueOnce(new Error('Unauthorized'));
    const { fetchCurrentUser } = useAuth();
    const result = await fetchCurrentUser();
    expect(result).toBeNull();
  });

  it('fetchCurrentUser returns null when success is false', async () => {
    mockApiFetch.mockResolvedValueOnce({ success: false, data: null });
    const { fetchCurrentUser } = useAuth();
    const result = await fetchCurrentUser();
    expect(result).toBeNull();
  });
});
