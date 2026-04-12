<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

// Reactively manage HTML dir and lang via useHead (works on SSR + client)
const htmlDir = computed(() => (locale.value === 'en' ? 'ltr' : 'rtl'));
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
    <NuxtPage />
  </GlobalErrorBoundary>
  <ErrorToast />
</template>
