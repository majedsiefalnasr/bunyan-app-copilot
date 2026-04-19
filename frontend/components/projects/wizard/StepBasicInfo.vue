<script setup lang="ts">
  import { ProjectType, type ProjectFormData } from '~/types/project';

  const model = defineModel<Partial<ProjectFormData>>({ default: () => ({}) });

  const { t } = useI18n();

  const typeOptions = Object.values(ProjectType).map((tp) => ({
    label: t(`projects.type.${tp}`),
    value: tp,
  }));
</script>

<template>
  <div class="space-y-4">
    <UFormGroup :label="t('projects.name_ar')" required>
      <UInput v-model="model.name_ar" dir="rtl" />
    </UFormGroup>
    <UFormGroup :label="t('projects.name_en')" required>
      <UInput v-model="model.name_en" dir="ltr" />
    </UFormGroup>
    <UFormGroup :label="t('projects.description')">
      <UTextarea v-model="model.description" :rows="3" />
    </UFormGroup>
    <UFormGroup :label="t('projects.type_label')" required>
      <USelectMenu
        v-model="model.type"
        :options="typeOptions"
        value-attribute="value"
        option-attribute="label"
      />
    </UFormGroup>
  </div>
</template>
