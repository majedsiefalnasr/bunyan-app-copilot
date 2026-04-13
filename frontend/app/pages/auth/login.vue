<script setup lang="ts">
    import { loginSchema, type LoginFormData } from '~/config/validation/auth';

    definePageMeta({
        layout: 'auth',
        middleware: ['guest'],
    });

    const { t, locale } = useI18n();
    const { login, isLoading } = useAuth();

    const state = reactive<LoginFormData>({
        email: '',
        password: '',
    });

    const serverError = ref<string | null>(null);

    async function onSubmit() {
        serverError.value = null;
        try {
            await login(state.email, state.password);
            await navigateTo(`/${locale.value}/dashboard`);
        } catch (error: unknown) {
            const err = error as { data?: { error?: { message?: string } } };
            serverError.value = err?.data?.error?.message || t('auth.login.error');
        }
    }
</script>

<template>
    <AuthCard :title="t('auth.login.title')" :subtitle="t('auth.login.subtitle')">
        <UForm :schema="loginSchema" :state="state" class="space-y-4" @submit="onSubmit">
            <UFormField :label="t('auth.login.email')" name="email">
                <UInput
                    v-model="state.email"
                    type="email"
                    :placeholder="t('auth.login.email_placeholder')"
                    icon="i-heroicons-envelope"
                    autocomplete="email"
                    class="w-full"
                />
            </UFormField>

            <UFormField :label="t('auth.login.password')" name="password">
                <UInput
                    v-model="state.password"
                    type="password"
                    :placeholder="t('auth.login.password_placeholder')"
                    icon="i-heroicons-lock-closed"
                    autocomplete="current-password"
                    class="w-full"
                />
            </UFormField>

            <div v-if="serverError" class="text-sm text-red-500">
                {{ serverError }}
            </div>

            <div class="flex items-center justify-between">
                <NuxtLink
                    :to="`/${locale}/auth/forgot-password`"
                    class="text-sm text-[#666] hover:text-[#171717] dark:text-[#888] dark:hover:text-[#ededed]"
                >
                    {{ t('auth.login.forgot_password') }}
                </NuxtLink>
            </div>

            <UButton type="submit" block :loading="isLoading" class="w-full">
                {{ t('auth.login.submit') }}
            </UButton>

            <p class="text-center text-sm text-[#666] dark:text-[#888]">
                {{ t('auth.login.no_account') }}
                <NuxtLink
                    :to="`/${locale}/auth/register`"
                    class="font-medium text-[#171717] hover:underline dark:text-[#ededed]"
                >
                    {{ t('auth.login.register_link') }}
                </NuxtLink>
            </p>
        </UForm>
    </AuthCard>
</template>
