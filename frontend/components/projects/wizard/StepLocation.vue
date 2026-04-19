<script setup lang="ts">
  import type { ProjectFormData } from '~/types/project';

  const model = defineModel<Partial<ProjectFormData>>({ default: () => ({}) });

  const { t } = useI18n();
</script>

<template>
  <div class="space-y-4">
    <UFormGroup :label="t('projects.city')" required>
      <UInput v-model="model.city" />
    </UFormGroup>
    <UFormGroup :label="t('projects.district')">
      <UInput v-model="model.district" />
    </UFormGroup>
    <div class="grid grid-cols-2 gap-4">
      <UFormGroup :label="t('projects.location_lat', 'خط العرض')">
        <UInput v-model.number="model.location_lat" type="number" step="any" min="-90" max="90" />
      </UFormGroup>
      <UFormGroup :label="t('projects.location_lng', 'خط الطول')">
        <UInput v-model.number="model.location_lng" type="number" step="any" min="-180" max="180" />
      </UFormGroup>
    </div>
  </div>
</template>
