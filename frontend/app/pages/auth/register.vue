<script setup lang="ts">
  import { registerSchema } from '~/config/validation/auth';

  definePageMeta({
    layout: 'auth',
    middleware: ['guest'],
  });

  const { t, locale } = useI18n();
  const { register, isLoading } = useAuth();

  const state = reactive({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    role: '' as 'customer' | 'contractor' | 'supervising_architect' | 'field_engineer',
  });

  const serverError = ref<string | null>(null);

  const roleOptions = computed(() => [
    { label: t('roles.customer'), value: 'customer' },
    { label: t('roles.contractor'), value: 'contractor' },
    { label: t('roles.supervising_architect'), value: 'supervising_architect' },
    { label: t('roles.field_engineer'), value: 'field_engineer' },
  ]);

  async function onSubmit() {
    serverError.value = null;
    try {
      await register(state);
      await navigateTo(`/${locale.value}/auth/verify-email`);
    } catch (error: unknown) {
      const err = error as { data?: { error?: { message?: string } } };
      serverError.value = err?.data?.error?.message || t('auth.register.error');
    }
  }
</script>

<template>
  <AuthCard :title="t('auth.register.title')" :subtitle="t('auth.register.subtitle')">
    <UForm :schema="registerSchema" :state="state" class="space-y-4" @submit="onSubmit">
      <UFormField :label="t('auth.register.name')" name="name">
        <UInput
          v-model="state.name"
          :placeholder="t('auth.register.name_placeholder')"
          icon="i-heroicons-user"
          autocomplete="name"
          class="w-full"
        />
      </UFormField>

      <UFormField :label="t('auth.register.email')" name="email">
        <UInput
          v-model="state.email"
          type="email"
          :placeholder="t('auth.register.email_placeholder')"
          icon="i-heroicons-envelope"
          autocomplete="email"
          class="w-full"
        />
      </UFormField>

      <UFormField :label="t('auth.register.phone')" name="phone">
        <UInput
          v-model="state.phone"
          type="tel"
          :placeholder="t('auth.register.phone_placeholder')"
          icon="i-heroicons-phone"
          autocomplete="tel"
          dir="ltr"
          class="w-full"
        />
      </UFormField>

      <UFormField :label="t('auth.register.password')" name="password">
        <UInput
          v-model="state.password"
          type="password"
          :placeholder="t('auth.register.password_placeholder')"
          icon="i-heroicons-lock-closed"
          autocomplete="new-password"
          class="w-full"
        />
      </UFormField>

      <UFormField :label="t('auth.register.password_confirmation')" name="password_confirmation">
        <UInput
          v-model="state.password_confirmation"
          type="password"
          :placeholder="t('auth.register.password_confirmation_placeholder')"
          icon="i-heroicons-lock-closed"
          autocomplete="new-password"
          class="w-full"
        />
      </UFormField>

      <UFormField :label="t('auth.register.role')" name="role">
        <USelect
          v-model="state.role"
          :options="roleOptions"
          :placeholder="t('auth.register.role_placeholder')"
          value-key="value"
          class="w-full"
        />
      </UFormField>

      <div v-if="serverError" class="text-sm text-red-500">
        {{ serverError }}
      </div>

      <UButton type="submit" block :loading="isLoading" class="w-full">
        {{ t('auth.register.submit') }}
      </UButton>

      <p class="text-center text-sm text-[#666] dark:text-[#888]">
        {{ t('auth.register.has_account') }}
        <NuxtLink
          :to="`/${locale}/auth/login`"
          class="font-medium text-[#171717] hover:underline dark:text-[#ededed]"
        >
          {{ t('auth.register.login_link') }}
        </NuxtLink>
      </p>
    </UForm>
  </AuthCard>
</template>
