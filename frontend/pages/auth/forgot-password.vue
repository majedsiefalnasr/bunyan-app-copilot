<template>
  <AuthLayout>
    <AuthCard
      :title="$t('auth.forgot_password.title')"
      :subtitle="$t('auth.forgot_password.subtitle')"
    >
      <form class="space-y-4" @submit.prevent="onSubmit">
        <!-- Error Alert -->
        <UAlert
          v-if="error"
          color="red"
          icon="i-heroicons-exclamation-circle"
          :description="error"
          @close="error = null"
        />

        <!-- Success Alert -->
        <UAlert
          v-if="success"
          color="green"
          icon="i-heroicons-check-circle"
          :description="$t('auth.forgot_password.success')"
        />

        <!-- Email Field -->
        <UFormGroup :label="$t('auth.forgot_password.email')" name="email" :error="errors.email">
          <UInput
            v-model="form.email"
            type="email"
            :placeholder="$t('auth.forgot_password.email_placeholder')"
            icon="i-heroicons-envelope"
          />
        </UFormGroup>

        <!-- Submit Button -->
        <UButton
          type="submit"
          :label="$t('auth.forgot_password.submit')"
          block
          :loading="isLoading"
          :disabled="isLoading || success"
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
  import { useAuth } from '~/composables/useAuth';
  import { useAuthSchemas } from '~/composables/useAuthSchemas';

  definePageMeta({
    middleware: 'guest',
  });

  const { locale } = useI18n();
  const auth = useAuth();
  const { forgotPasswordSchema } = useAuthSchemas();

  const form = reactive({
    email: '',
  });

  const errors = reactive({
    email: '',
  });

  const error = ref('');
  const success = ref(false);
  const isLoading = ref(false);

  const onSubmit = async () => {
    error.value = '';
    success.value = false;

    try {
      forgotPasswordSchema.parse(form);
      errors.email = '';
    } catch (e) {
      const err = e as { errors?: Array<{ path: string[]; message: string }> };
      for (const errItem of err.errors ?? []) {
        if (errItem.path[0] === 'email') {
          errors.email = errItem.message;
        }
      }
      return;
    }

    isLoading.value = true;

    try {
      await auth.forgotPassword(form.email);
      success.value = true;
    } catch (err) {
      const error = err as { response?: { data?: { error?: { message?: string } } } };
      const message =
        error.response?.data?.error?.message || 'فشل إرسال البريد / Failed to send email';
      error.value = message;
    } finally {
      isLoading.value = false;
    }
  };
</script>
