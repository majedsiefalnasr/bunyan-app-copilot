<script setup lang="ts">
    import { resetPasswordSchema } from '~/app/config/validation/auth';

    definePageMeta({
        layout: 'auth',
        middleware: ['guest'],
    });

    const { t, locale } = useI18n();
    const route = useRoute();
    const { resetPassword, isLoading } = useAuth();
    const toast = useToast();

    const state = reactive({
        email: (route.query.email as string) || '',
        token: (route.query.token as string) || '',
        password: '',
        password_confirmation: '',
    });

    const serverError = ref<string | null>(null);

    async function onSubmit() {
        serverError.value = null;
        try {
            await resetPassword(state);
            toast.add({
                title: t('auth.reset_password.success'),
                color: 'success',
            });
            await navigateTo(`/${locale.value}/auth/login`);
        } catch (error: unknown) {
            const err = error as { data?: { error?: { message?: string } } };
            serverError.value = err?.data?.error?.message || t('auth.reset_password.error');
        }
    }
</script>

<template>
    <AuthCard :title="t('auth.reset_password.title')" :subtitle="t('auth.reset_password.subtitle')">
        <UForm :schema="resetPasswordSchema" :state="state" class="space-y-4" @submit="onSubmit">
            <input type="hidden" name="email" :value="state.email" />
            <input type="hidden" name="token" :value="state.token" />

            <UFormField :label="t('auth.reset_password.password')" name="password">
                <UInput
                    v-model="state.password"
                    type="password"
                    :placeholder="t('auth.reset_password.password_placeholder')"
                    icon="i-heroicons-lock-closed"
                    autocomplete="new-password"
                    class="w-full"
                />
            </UFormField>

            <UFormField
                :label="t('auth.reset_password.password_confirmation')"
                name="password_confirmation"
            >
                <UInput
                    v-model="state.password_confirmation"
                    type="password"
                    :placeholder="t('auth.reset_password.password_confirmation_placeholder')"
                    icon="i-heroicons-lock-closed"
                    autocomplete="new-password"
                    class="w-full"
                />
            </UFormField>

            <div v-if="serverError" class="text-sm text-red-500">
                {{ serverError }}
            </div>

            <UButton type="submit" block :loading="isLoading" class="w-full">
                {{ t('auth.reset_password.submit') }}
            </UButton>
        </UForm>
    </AuthCard>
</template>
