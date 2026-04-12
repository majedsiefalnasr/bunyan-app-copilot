<script setup lang="ts">
    import { computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useDirection } from '../composables/useDirection';

    const { locale } = useI18n();
    const { direction } = useDirection();

    // Reactively manage HTML dir and lang via useHead (works on SSR + client).
    // direction ref handles manual RTL/LTR override; falls back to locale-derived value.
    const htmlDir = computed(() => direction.value);
    const htmlLang = computed(() => (locale.value === 'en' ? 'en-US' : 'ar-SA'));

    useHead({
        htmlAttrs: {
            dir: htmlDir,
            lang: htmlLang,
        },
    });
</script>

<template>
    <NuxtRouteAnnouncer />
    <GlobalErrorBoundary>
        <NuxtLayout>
            <NuxtPage />
        </NuxtLayout>
    </GlobalErrorBoundary>
    <ErrorToast />
</template>
