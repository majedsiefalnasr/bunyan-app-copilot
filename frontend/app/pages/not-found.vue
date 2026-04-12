<script setup lang="ts">
    import { useI18n } from 'vue-i18n';
    import { useLocaleRoute } from '../../composables/useLocaleRoute';

    const localeRoute = useLocaleRoute();
    const { t, locale } = useI18n();

    function goHome() {
        if (import.meta.client) {
            window.location.assign(`/${locale.value}`);
        }
    }

    function goBack() {
        localeRoute.back();
    }
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-white px-4">
        <div class="max-w-md w-full">
            <!-- 404 Icon -->
            <div class="flex justify-center mb-6">
                <div class="text-6xl font-bold text-gray-200">404</div>
            </div>

            <!-- Heading -->
            <h1 class="text-2xl font-semibold text-center mb-2 text-[#171717] tracking-tight">
                {{ t('errors.not_found_title', 'Page Not Found') }}
            </h1>

            <!-- Description -->
            <p class="text-sm text-[#666] text-center mb-8">
                {{
                    t(
                        'errors.not_found_message',
                        'The resource you are looking for does not exist or has been removed.'
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
                    :label="t('errors.go_back', 'Go Back')"
                    class="rounded-[6px]"
                    variant="outline"
                    @click="goBack"
                />
            </div>
        </div>
    </div>
</template>
