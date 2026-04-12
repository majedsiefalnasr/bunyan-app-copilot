<script setup lang="ts">
import { watch } from 'vue';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const { locale } = useI18n();

// Guest guard: redirect authenticated users to their role dashboard
watch(
  () => authStore.isAuthenticated,
  (isAuthenticated) => {
    if (isAuthenticated) {
      navigateTo(`/${locale.value}/dashboard`);
    }
  },
  { immediate: true }
);
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#fafafa] dark:bg-[#0a0a0a] p-4">
    <UCard
      class="w-full max-w-md"
      :ui="{
        root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]',
      }"
    >
      <template #header>
        <div class="flex justify-center py-2">
          <img src="/logo.svg" alt="بنيان" class="h-8" >
        </div>
      </template>

      <slot />
    </UCard>
  </div>
</template>
