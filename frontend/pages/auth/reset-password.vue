<template>
  <AuthLayout>
    <AuthCard
      :title="$t('auth.reset_password.title')"
      :subtitle="$t('auth.reset_password.subtitle')"
    >
      <!-- Token Expired Alert -->
      <UAlert
        v-if="tokenExpired"
        color="red"
        icon="i-heroicons-exclamation-circle"
        :description="$t('auth.reset_password.token_expired')"
        class="mb-4"
      />

      <form v-else class="space-y-4" @submit.prevent="onSubmit">
        <!-- Error Alert -->
        <UAlert
          v-if="error"
          color="red"
          icon="i-heroicons-exclamation-circle"
          :description="error"
          @close="error = null"
        />

        <!-- Password Field -->
        <UFormGroup
          :label="$t('auth.reset_password.password')"
          name="password"
          :error="errors.password"
        >
          <div class="flex gap-2">
            <UInput
              v-model="form.password"
              :type="passwordToggle.type"
              :placeholder="$t('auth.reset_password.password_placeholder')"
              icon="i-heroicons-lock-closed"
              class="flex-1"
              @input="updatePasswordStrength"
            />
            <UButton
              :icon="passwordToggle.icon"
              color="gray"
              variant="ghost"
              :aria-label="passwordToggle.ariaLabel"
              @click="passwordToggle.toggle"
            />
          </div>

          <!-- Password Strength Indicator -->
          <PasswordStrength :score="passwordStrength" class="mt-2" />
        </UFormGroup>

        <!-- Confirm Password Field -->
        <UFormGroup
          :label="$t('auth.reset_password.password_confirmation')"
          name="confirmPassword"
          :error="errors.confirmPassword"
        >
          <div class="flex gap-2">
            <UInput
              v-model="form.confirmPassword"
              :type="passwordToggle.type"
              :placeholder="$t('auth.reset_password.password_confirmation_placeholder')"
              icon="i-heroicons-lock-closed"
              class="flex-1"
            />
            <UButton
              :icon="passwordToggle.icon"
              color="gray"
              variant="ghost"
              :aria-label="passwordToggle.ariaLabel"
              @click="passwordToggle.toggle"
            />
          </div>
        </UFormGroup>

        <!-- Submit Button -->
        <UButton
          type="submit"
          :label="$t('auth.reset_password.submit')"
          block
          :loading="isLoading"
          :disabled="isLoading"
          color="neutral"
        />

        <!-- Back to Login Link -->
        <div class="text-center">
          <NuxtLink
            :to="`/${locale}/auth/login`"
            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
          >
            {{ $t('auth.forgot_password.back_to_login') }}
          </NuxtLink>
        </div>
      </form>
    </AuthCard>
  </AuthLayout>
</template>

<script setup lang="ts">
  import { ref, reactive } from 'vue';
  import { useI18n } from 'vue-i18n';
  import { useRouter, useRoute } from 'vue-router';
  import { useAuth } from '~/composables/useAuth';
  import { usePasswordToggle } from '~/composables/usePasswordToggle';
  import { useAuthSchemas } from '~/composables/useAuthSchemas';

  definePageMeta({
    middleware: 'guest',
  });

  const router = useRouter();
  const route = useRoute();
  const { locale } = useI18n();
  const auth = useAuth();
  const passwordToggle = usePasswordToggle();
  const { resetPasswordSchema } = useAuthSchemas();

  const token = route.query.token as string;
  const tokenExpired = ref(false);
  const passwordStrength = ref(0);
  const isLoading = ref(false);
  const error = ref('');

  const form = reactive({
    password: '',
    confirmPassword: '',
  });

  const errors = reactive({
    password: '',
    confirmPassword: '',
  });

  // Validate token on mount
  onMounted(async () => {
    if (!token) {
      tokenExpired.value = true;
    }
    // In a real app, you'd validate the token with the backend
    // For now, we assume the token is valid
  });

  const updatePasswordStrength = () => {
    if (!form.password) {
      passwordStrength.value = 0;
      return;
    }

    let strength = 0;

    if (form.password.length >= 8) strength += 20;
    if (form.password.length >= 12) strength += 10;
    if (/[a-z]/.test(form.password)) strength += 15;
    if (/[A-Z]/.test(form.password)) strength += 15;
    if (/\d/.test(form.password)) strength += 15;
    if (/[!@#$%^&*]/.test(form.password)) strength += 25;

    passwordStrength.value = Math.min(strength, 100);
  };

  const onSubmit = async () => {
    error.value = '';

    try {
      resetPasswordSchema.parse(form);
      errors.password = '';
      errors.confirmPassword = '';
    } catch (e) {
      const err = e as { errors?: Array<{ path: string[]; message: string }> };
      for (const errItem of err.errors ?? []) {
        if (errItem.path[0] === 'password') {
          errors.password = errItem.message;
        } else if (errItem.path[0] === 'confirmPassword') {
          errors.confirmPassword = errItem.message;
        }
      }
      return;
    }

    isLoading.value = true;

    try {
      await auth.resetPassword({
        email: '',
        token: token || '',
        password: form.password,
        password_confirmation: form.confirmPassword,
      });

      // Show success and redirect to login
      await router.push(`/${locale.value}/auth/login`);
    } catch (err) {
      const error = err as {
        response?: { data?: { error?: { code?: string; message?: string } } };
      };
      const errorCode = error.response?.data?.error?.code;
      const errorMessage = error.response?.data?.error?.message;
      if (errorCode === 'WORKFLOW_PREREQUISITES_UNMET') {
        tokenExpired.value = true;
      } else {
        error.value = errorMessage || 'فشل إعادة تعيين كلمة المرور / Reset failed';
      }
    } finally {
      isLoading.value = false;
    }
  };
</script>
