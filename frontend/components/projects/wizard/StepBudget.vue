<script setup lang="ts">
  import type { ProjectFormData } from '~/types/project';

  const model = defineModel<Partial<ProjectFormData>>({ default: () => ({}) });

  const { t } = useI18n();
</script>

<template>
  <div class="space-y-4">
    <UFormGroup :label="t('projects.budget_estimated')">
      <UInput v-model.number="model.budget_estimated" type="number" min="0" step="0.01" />
    </UFormGroup>
    <div class="grid grid-cols-2 gap-4">
      <UFormGroup :label="t('projects.start_date')">
        <UInput v-model="model.start_date" type="date" />
      </UFormGroup>
      <UFormGroup :label="t('projects.end_date')">
        <UInput v-model="model.end_date" type="date" />
      </UFormGroup>
    </div>
  </div>
</template>
