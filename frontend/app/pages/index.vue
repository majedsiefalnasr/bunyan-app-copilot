<script setup lang="ts">
  const { t } = useI18n();
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-white">
    <h1 class="text-2xl font-semibold text-[#171717]">{{ t('welcome', 'Welcome') }}</h1>
  </div>
</template>
