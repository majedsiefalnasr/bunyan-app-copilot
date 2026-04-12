<script setup lang="ts">
    import { useI18n } from 'vue-i18n';

    const { t, locale } = useI18n();

    function goHome() {
        if (import.meta.client) {
            window.location.assign(`/${locale.value}`);
        }
    }

    function contactAdmin() {
        alert(t('errors.contact_admin_message', 'Please contact the administrator for help.'));
    }
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-white px-4">
        <div class="max-w-md w-full">
            <!-- 403 Icon -->
            <div class="flex justify-center mb-6">
                <div class="text-6xl font-bold text-gray-200">403</div>
            </div>

            <!-- Heading -->
            <h1 class="text-2xl font-semibold text-center mb-2 text-[#171717] tracking-tight">
                {{ t('errors.access_denied_title', 'Access Denied') }}
            </h1>

            <!-- Description -->
            <p class="text-sm text-[#666] text-center mb-2">
                {{
                    t(
                        'errors.access_denied_message',
                        'You do not have permission to access this resource.'
                    )
                }}
            </p>

            <p class="text-sm text-[#666] text-center mb-8">
                {{
                    t(
                        'errors.contact_admin_info',
                        'If you believe this is a mistake, please contact the administrator.'
                    )
                }}
            </p>

            <!-- Buttons -->
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                <UButton
                    :label="t('errors.go_home', 'Go to Home')"
                    class="rounded-[6px]"
                    color="neutral"
                    @click="goHome"
                />
                <UButton
                    :label="t('errors.contact_admin', 'Contact Admin')"
                    class="rounded-[6px]"
                    variant="outline"
                    @click="contactAdmin"
                />
            </div>
        </div>
    </div>
</template>
