<script setup lang="ts">
  import { forgotPasswordSchema, type ForgotPasswordFormData } from '~/config/validation/auth';

  definePageMeta({
    layout: 'auth',
    middleware: ['guest'],
  });

  const { t, locale } = useI18n();
  const { forgotPassword, isLoading } = useAuth();

  const state = reactive<ForgotPasswordFormData>({
    email: '',
  });

  const submitted = ref(false);
  const serverError = ref<string | null>(null);

  async function onSubmit() {
    serverError.value = null;
    try {
      await forgotPassword(state.email);
      submitted.value = true;
    } catch {
      // Always show success to prevent email enumeration
      submitted.value = true;
    }
  }
</script>

<template>
  <AuthCard :title="t('auth.forgot_password.title')" :subtitle="t('auth.forgot_password.subtitle')">
    <div v-if="submitted" class="space-y-4">
      <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-4">
        <p class="text-sm text-green-800 dark:text-green-200">
          {{ t('auth.forgot_password.success') }}
        </p>
      </div>
      <NuxtLink :to="`/${locale}/auth/login`">
        <UButton variant="outline" block class="w-full">
          {{ t('auth.forgot_password.back_to_login') }}
        </UButton>
      </NuxtLink>
    </div>

    <UForm
      v-else
      :schema="forgotPasswordSchema"
      :state="state"
      class="space-y-4"
      @submit="onSubmit"
    >
      <UFormField :label="t('auth.forgot_password.email')" name="email">
        <UInput
          v-model="state.email"
          type="email"
          :placeholder="t('auth.forgot_password.email_placeholder')"
          icon="i-heroicons-envelope"
          autocomplete="email"
          class="w-full"
        />
      </UFormField>

      <UButton type="submit" block :loading="isLoading" class="w-full">
        {{ t('auth.forgot_password.submit') }}
      </UButton>

      <p class="text-center">
        <NuxtLink
          :to="`/${locale}/auth/login`"
          class="text-sm text-[#666] hover:text-[#171717] dark:text-[#888] dark:hover:text-[#ededed]"
        >
          {{ t('auth.forgot_password.back_to_login') }}
        </NuxtLink>
      </p>
    </UForm>
  </AuthCard>
</template>
