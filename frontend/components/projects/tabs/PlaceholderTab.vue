<script setup lang="ts">
  const { t } = useI18n();
</script>

<template>
  <div class="flex flex-col items-center justify-center py-16 text-gray-400">
    <UIcon name="i-heroicons-clock" class="mb-3 size-12" />
    <p class="text-sm">{{ t('projects.coming_soon', 'قريبًا في تحديث مستقبلي') }}</p>
  </div>
</template>
