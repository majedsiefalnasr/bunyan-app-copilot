<template>
  <AuthLayout>
    <AuthCard :title="$t('auth.login.title')" :subtitle="$t('auth.login.subtitle')">
      <form class="space-y-4" @submit.prevent="onSubmit">
        <!-- Error Alert -->
        <UAlert
          v-if="error"
          :title="$t('errors.error')"
          color="error"
          icon="i-heroicons-exclamation-circle"
          :description="error"
          @close="error = ''"
        />

        <!-- Account Locked Alert -->
        <UAlert
          v-if="isAccountLocked"
          :title="$t('errors.account_locked')"
          color="error"
          icon="i-heroicons-lock-closed"
          :description="`حسابك مقفول. حاول مجدداً بعد ${accountLockCountdown} دقيقة / Account locked. Try again in ${accountLockCountdown} minute${accountLockCountdown > 1 ? 's' : ''}.`"
        />

        <!-- Rate Limit Alert with Countdown -->
        <UAlert
          v-if="isRateLimited"
          title="محاولات كثيرة / Too many attempts"
          color="warning"
          icon="i-heroicons-clock"
          :description="`حاول مجدداً بعد ${rateLimitCountdown} ثانية / Try again in ${rateLimitCountdown} second${rateLimitCountdown > 1 ? 's' : ''}.`"
        />

        <!-- Email Field -->
        <UFormGroup :label="$t('auth.login.email')" name="email" :error="errors.email">
          <UInput
            v-model="form.email"
            type="email"
            :placeholder="$t('auth.login.email_placeholder')"
            icon="i-heroicons-envelope"
            :disabled="isAccountLocked || isRateLimited"
            @blur="validateEmail"
          />
        </UFormGroup>

        <!-- Password Field -->
        <UFormGroup :label="$t('auth.login.password')" name="password" :error="errors.password">
          <div class="flex gap-2">
            <UInput
              v-model="form.password"
              :type="passwordToggle.type.value"
              :placeholder="$t('auth.login.password_placeholder')"
              icon="i-heroicons-lock-closed"
              :disabled="isAccountLocked || isRateLimited"
              class="flex-1"
            />
            <UButton
              :icon="passwordToggle.icon"
              color="neutral"
              variant="ghost"
              :disabled="isAccountLocked || isRateLimited"
              :aria-label="passwordToggle.ariaLabel"
              @click="passwordToggle.toggle"
            />
          </div>
        </UFormGroup>

        <!-- Remember Me Checkbox -->
        <UCheckbox
          v-model="form.rememberMe"
          :label="$t('auth.login.remember_me')"
          :disabled="isAccountLocked || isRateLimited"
        />

        <!-- Submit Button -->
        <UButton
          type="submit"
          :label="
            isAccountLocked
              ? `مقفول / Locked ${accountLockCountdown}m`
              : isRateLimited
                ? `حاول بعد قليل / Retry in ${rateLimitCountdown}s`
                : $t('auth.login.submit')
          "
          block
          :loading="isLoading"
          :disabled="isLoading || isAccountLocked || isRateLimited"
          color="neutral"
        />

        <!-- Links -->
        <div class="space-y-2 text-sm">
          <div class="flex justify-center">
            <NuxtLink
              :to="`/${locale}/auth/forgot-password`"
              class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            >
              {{ $t('auth.login.forgot_password') }}
            </NuxtLink>
          </div>

          <div class="flex justify-center gap-1">
            <span class="text-[#666666] dark:text-[#999999]">
              {{ $t('auth.login.no_account') }}
            </span>
            <NuxtLink
              :to="`/${locale}/auth/register`"
              class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
            >
              {{ $t('auth.login.register_link') }}
            </NuxtLink>
          </div>
        </div>
      </form>
    </AuthCard>
  </AuthLayout>
</template>

<script setup lang="ts">
  import { onMounted, onUnmounted, reactive, ref } from 'vue';
  import { useI18n } from 'vue-i18n';
  import { useRouter } from 'vue-router';
  import { useAuth } from '../../composables/useAuth';
  import { useAuthSchemas } from '../../composables/useAuthSchemas';
  import { usePasswordToggle } from '../../composables/usePasswordToggle';

  definePageMeta({
    layout: 'default', // Will be overridden by AuthLayout
    middleware: 'guest',
  });

  const router = useRouter();
  const { locale } = useI18n();
  const auth = useAuth();
  const passwordToggle = usePasswordToggle();
  const { loginSchema } = useAuthSchemas();

  const form = reactive({
    email: '',
    password: '',
    rememberMe: false,
  });

  const errors = reactive({
    email: '',
    password: '',
  });

  const error = ref('');
  const isLoading = ref(false);

  // Rate limiting state
  const isRateLimited = ref(false);
  const rateLimitCountdown = ref(0);
  let rateLimitTimer: NodeJS.Timeout | null = null;

  // Account lockout state
  const isAccountLocked = ref(false);
  const accountLockCountdown = ref(0); // in minutes
  let accountLockTimer: NodeJS.Timeout | null = null;

  onMounted(() => {
    // Check for rate limit or account lock stored in sessionStorage
    const storedRateLimit = sessionStorage.getItem('login_rate_limit');
    const storedLock = sessionStorage.getItem('login_account_lock');

    if (storedRateLimit) {
      const expiryTime = parseInt(storedRateLimit);
      const now = Date.now();
      const remaining = Math.ceil((expiryTime - now) / 1000);

      if (remaining > 0) {
        initiateRateLimitCountdown(remaining);
      } else {
        sessionStorage.removeItem('login_rate_limit');
      }
    }

    if (storedLock) {
      const expiryTime = parseInt(storedLock);
      const now = Date.now();
      const remainingMinutes = Math.ceil((expiryTime - now) / 60000);

      if (remainingMinutes > 0) {
        initiateAccountLockCountdown(remainingMinutes);
      } else {
        sessionStorage.removeItem('login_account_lock');
      }
    }
  });

  onUnmounted(() => {
    if (rateLimitTimer) clearInterval(rateLimitTimer);
    if (accountLockTimer) clearInterval(accountLockTimer);
  });

  const initiateRateLimitCountdown = (seconds: number) => {
    isRateLimited.value = true;
    rateLimitCountdown.value = seconds;

    // Store in sessionStorage for persistence across page reloads
    const expiryTime = Date.now() + seconds * 1000;
    sessionStorage.setItem('login_rate_limit', expiryTime.toString());

    rateLimitTimer = setInterval(() => {
      rateLimitCountdown.value--;
      if (rateLimitCountdown.value <= 0) {
        isRateLimited.value = false;
        sessionStorage.removeItem('login_rate_limit');
        if (rateLimitTimer) clearInterval(rateLimitTimer);
      }
    }, 1000);
  };

  const initiateAccountLockCountdown = (minutes: number) => {
    isAccountLocked.value = true;
    accountLockCountdown.value = minutes;

    // Store in sessionStorage for persistence
    const expiryTime = Date.now() + minutes * 60000;
    sessionStorage.setItem('login_account_lock', expiryTime.toString());

    accountLockTimer = setInterval(() => {
      accountLockCountdown.value--;
      if (accountLockCountdown.value <= 0) {
        isAccountLocked.value = false;
        sessionStorage.removeItem('login_account_lock');
        if (accountLockTimer) clearInterval(accountLockTimer);
      }
    }, 60000); // Update every minute
  };

  const validateEmail = () => {
    try {
      const emailSchema = loginSchema.pick({ email: true });
      emailSchema.parse({ email: form.email });
      errors.email = '';
    } catch (e) {
      const err = e as { errors?: Array<{ path: string[]; message: string }> };
      errors.email = err.errors?.[0]?.message || '';
    }
  };

  const onSubmit = async () => {
    // Check if rate limited or locked
    if (isRateLimited.value || isAccountLocked.value) {
      error.value = 'حاول بعد قليل / Please try again later';
      return;
    }

    // Clear previous error
    error.value = '';

    // Validate form
    try {
      loginSchema.parse(form);
      errors.email = '';
      errors.password = '';
    } catch (e) {
      const err = e as { errors?: Array<{ path: string[]; message: string }> };
      for (const errItem of err.errors ?? []) {
        if (errItem.path[0] === 'email') {
          errors.email = errItem.message;
        } else if (errItem.path[0] === 'password') {
          errors.password = errItem.message;
        }
      }
      return;
    }

    isLoading.value = true;

    try {
      await auth.login(form.email, form.password);
      // Redirect to dashboard on success
      sessionStorage.removeItem('login_rate_limit');
      sessionStorage.removeItem('login_account_lock');
      await router.push(`/${locale.value}/dashboard`);
    } catch (err) {
      const errorData = err as {
        response?: { data?: { error?: { code?: string; message?: string } } };
      };
      const errorCode = errorData.response?.data?.error?.code;
      const errorMessage = errorData.response?.data?.error?.message;
      if (errorCode === 'RATE_LIMIT_EXCEEDED') {
        // Initiate 15-minute rate limit (900 seconds)
        initiateRateLimitCountdown(900);
        error.value = '';
      } else if (errorCode === 'AUTH_UNAUTHORIZED') {
        // Initiate 15-minute account lockout
        initiateAccountLockCountdown(15);
        error.value = '';
      } else {
        error.value = errorMessage || 'فشل تسجيل الدخول / Login failed';
      }
    } finally {
      isLoading.value = false;
    }
  };
</script>
