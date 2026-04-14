<template>
  <AuthLayout>
    <AuthCard :title="$t('auth.verify_email.title')" :subtitle="$t('auth.verify_email.subtitle')">
      <form class="space-y-6" @submit.prevent="onSubmit">
        <!-- Message -->
        <div class="text-center">
          <p class="text-sm text-[#666666] dark:text-[#999999]">
            {{ $t('auth.verify_email.message') }}
          </p>
          <p class="text-sm font-semibold text-[#171717] dark:text-white mt-1">
            {{ maskedEmail }}
          </p>
        </div>

        <!-- Error Alert -->
        <UAlert
          v-if="error"
          color="red"
          icon="i-heroicons-exclamation-circle"
          :description="error"
          @close="error = null"
        />

        <!-- Rate Limit Alert -->
        <UAlert
          v-if="isOtpLocked"
          color="red"
          icon="i-heroicons-lock-closed"
          :description="`تم الوصول لحد محاولات التحقق. حاول بعد ${otpLockCountdown} دقيقة / Too many OTP attempts. Try again in ${otpLockCountdown} minute${otpLockCountdown > 1 ? 's' : ''}.`"
        />

        <!-- OTP Input -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-medium text-[#171717] dark:text-white">
              {{ $t('auth.verify_email.code_label') }}
            </label>
            <div class="text-xs text-[#999999] flex gap-3">
              <!-- Attempt Counter -->
              <span v-if="!isOtpLocked">
                المحاولة {{ otpAttempts }}/5 | Attempt {{ otpAttempts }}/5
              </span>
              <!-- Code Expiry Timer -->
              <span v-if="codeExpiryCountdown > 0">
                ينتهي بعد {{ codeExpiryCountdown }}m | Expires in {{ codeExpiryCountdown }}m
              </span>
              <span v-else-if="codeExpired" class="text-red-600"> انتهت الصلاحية / Expired </span>
            </div>
          </div>
          <OtpInput v-model="code" :disabled="isOtpLocked" @complete="autoSubmit" />
        </div>

        <!-- Submit Button -->
        <UButton
          type="submit"
          :label="
            isOtpLocked ? `مقفول / Locked ${otpLockCountdown}m` : $t('auth.verify_email.submit')
          "
          block
          :loading="isLoading"
          :disabled="isLoading || code.length < 6 || isOtpLocked || codeExpired"
          color="neutral"
        />

        <!-- Resend Code -->
        <div class="text-center space-y-2">
          <UButton
            v-if="canResend"
            :label="$t('auth.verify_email.resend')"
            variant="outline"
            size="sm"
            :disabled="isLoading || isOtpLocked"
            @click="onResend"
          />
          <div v-else-if="!isOtpLocked" class="text-xs text-[#999999]">
            {{ $t('auth.verify_email.resend_countdown', { seconds: resendCountdown }) }}
          </div>
        </div>

        <!-- Change Email Link -->
        <div class="text-center">
          <NuxtLink
            :to="`/${locale}/auth/register`"
            class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
          >
            {{ $t('auth.verify_email.change_email') }}
          </NuxtLink>
        </div>
      </form>
    </AuthCard>
  </AuthLayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, onUnmounted, ref } from 'vue';
  import { useI18n } from 'vue-i18n';
  import { useRoute, useRouter } from 'vue-router';
  import { useAuth } from '~/composables/useAuth';

  definePageMeta({
    middleware: 'guest',
  });

  const router = useRouter();
  const route = useRoute();
  const { locale } = useI18n();
  const auth = useAuth();

  const email = (route.query.email as string) || '';
  const code = ref('');
  const error = ref('');
  const isLoading = ref(false);
  const canResend = ref(true);
  const resendCountdown = ref(0);
  let resendTimer: NodeJS.Timeout | null = null;

  // OTP attempt tracking
  const otpAttempts = ref(0);
  const MAX_OTP_ATTEMPTS = 5;
  const isOtpLocked = ref(false);
  const otpLockCountdown = ref(0);
  let otpLockTimer: NodeJS.Timeout | null = null;

  // Code expiry tracking
  const codeExpiryCountdown = ref(10); // 10 minutes default
  let codeExpiryTimer: NodeJS.Timeout | null = null;
  const codeExpired = ref(false);

  const maskedEmail = computed(() => {
    if (!email) return '';
    const [local, domain] = email.split('@');
    return `${local.substring(0, 3)}****@${domain}`;
  });

  onMounted(() => {
    // Check sessionStorage for OTP lock state
    const storedOtpAttempts = sessionStorage.getItem('otp_attempts');
    const storedOtpLock = sessionStorage.getItem('otp_lock_time');

    if (storedOtpAttempts) {
      otpAttempts.value = parseInt(storedOtpAttempts);
    }

    if (storedOtpLock) {
      const expiryTime = parseInt(storedOtpLock);
      const now = Date.now();
      const remainingMinutes = Math.ceil((expiryTime - now) / 60000);

      if (remainingMinutes > 0) {
        initiateOtpLock(remainingMinutes);
      } else {
        sessionStorage.removeItem('otp_lock_time');
      }
    }

    // Start code expiry countdown (10 minutes)
    startCodeExpiryTimer();
  });

  onUnmounted(() => {
    if (resendTimer) clearInterval(resendTimer);
    if (otpLockTimer) clearInterval(otpLockTimer);
    if (codeExpiryTimer) clearInterval(codeExpiryTimer);
  });

  const startCodeExpiryTimer = () => {
    codeExpiryCountdown.value = 10;
    codeExpired.value = false;

    codeExpiryTimer = setInterval(() => {
      codeExpiryCountdown.value--;
      if (codeExpiryCountdown.value <= 0) {
        codeExpired.value = true;
        if (codeExpiryTimer) clearInterval(codeExpiryTimer);
      }
    }, 60000); // Update every minute
  };

  const initiateOtpLock = (minutes: number) => {
    isOtpLocked.value = true;
    otpLockCountdown.value = minutes;

    // Store in sessionStorage
    const expiryTime = Date.now() + minutes * 60000;
    sessionStorage.setItem('otp_lock_time', expiryTime.toString());

    otpLockTimer = setInterval(() => {
      otpLockCountdown.value--;
      if (otpLockCountdown.value <= 0) {
        isOtpLocked.value = false;
        sessionStorage.removeItem('otp_lock_time');
        sessionStorage.removeItem('otp_attempts');
        otpAttempts.value = 0;
        if (otpLockTimer) clearInterval(otpLockTimer);
      }
    }, 60000); // Update every minute
  };

  const autoSubmit = async () => {
    if (code.value.length === 6) {
      await onSubmit();
    }
  };

  const onSubmit = async () => {
    if (isOtpLocked.value) {
      error.value = 'تم الوصول لحد المحاولات القصوى / Too many attempts';
      return;
    }

    if (codeExpired.value) {
      error.value = 'انتهت صلاحية الرمز / Code expired';
      return;
    }

    error.value = '';

    if (code.value.length !== 6) {
      error.value = 'رمز التحقق يجب أن يكون 6 أرقام / Code must be 6 digits';
      return;
    }

    isLoading.value = true;

    try {
      await auth.verifyEmail(code.value);
      // Clear attempt tracking on success
      sessionStorage.removeItem('otp_attempts');
      sessionStorage.removeItem('otp_lock_time');
      // Redirect to dashboard
      await router.push(`/${locale.value}/dashboard`);
    } catch (err) {
      const error = err as {
        response?: { data?: { error?: { code?: string; message?: string } } };
      };
      const errorCode = error.response?.data?.error?.code;
      const errorMessage = error.response?.data?.error?.message;

      // Increment attempt counter
      otpAttempts.value++;
      sessionStorage.setItem('otp_attempts', otpAttempts.value.toString());

      if (otpAttempts.value >= MAX_OTP_ATTEMPTS) {
        // Lock OTP input for 10 minutes
        initiateOtpLock(10);
        error.value = 'تم الوصول لحد المحاولات القصوى / Too many attempts';
      } else if (errorCode === 'RATE_LIMIT_EXCEEDED') {
        error.value = `عدد محاولات التحقق كثير جداً (${MAX_OTP_ATTEMPTS - otpAttempts.value} متبقية) / Too many attempts (${MAX_OTP_ATTEMPTS - otpAttempts.value} remaining)`;
      } else if (errorCode === 'WORKFLOW_PREREQUISITES_UNMET') {
        error.value = 'انتهت صلاحية الرمز / Code expired';
      } else {
        error.value = errorMessage || 'فشل التحقق / Verification failed';
      }
    } finally {
      isLoading.value = false;
    }
  };

  const onResend = async () => {
    isLoading.value = true;

    try {
      await auth.resendVerification();
      canResend.value = false;
      resendCountdown.value = 60;
      // Reset OTP attempts on resend
      otpAttempts.value = 0;
      sessionStorage.removeItem('otp_attempts');
      // Reset code expiry
      startCodeExpiryTimer();

      // Start countdown
      resendTimer = setInterval(() => {
        resendCountdown.value--;
        if (resendCountdown.value <= 0) {
          canResend.value = true;
          if (resendTimer) clearInterval(resendTimer);
        }
      }, 1000);
    } catch {
      error.value = 'فشل إعادة إرسال الرمز / Failed to resend code';
    } finally {
      isLoading.value = false;
    }
  };
</script>
