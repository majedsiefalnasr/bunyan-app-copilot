<script setup lang="ts">
    definePageMeta({
        layout: 'auth',
        middleware: ['auth'],
    });

    const { t } = useI18n();
    const { user, resendVerification, isLoading } = useAuth();
    const toast = useToast();

    const resendCooldown = ref(false);

    async function onResend() {
        if (resendCooldown.value) return;
        try {
            await resendVerification();
            toast.add({
                title: t('auth.verify_email.resend_success'),
                color: 'success',
            });
            resendCooldown.value = true;
            setTimeout(() => {
                resendCooldown.value = false;
            }, 60000);
        } catch {
            toast.add({
                title: t('auth.verify_email.resend_error'),
                color: 'error',
            });
        }
    }
</script>

<template>
    <AuthCard :title="t('auth.verify_email.title')" :subtitle="t('auth.verify_email.subtitle')">
        <div v-if="user?.email_verified_at" class="space-y-4">
            <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-4">
                <p class="text-sm text-green-800 dark:text-green-200">
                    {{ t('auth.verify_email.already_verified') }}
                </p>
            </div>
        </div>

        <div v-else class="space-y-4">
            <div class="rounded-md bg-blue-50 dark:bg-blue-900/20 p-4">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    {{ t('auth.verify_email.message') }}
                </p>
            </div>

            <UButton
                block
                :loading="isLoading"
                :disabled="resendCooldown.value"
                class="w-full"
                @click="onResend"
            >
                {{ t('auth.verify_email.resend') }}
            </UButton>
        </div>
    </AuthCard>
</template>
