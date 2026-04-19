<script setup lang="ts">
  import { ProjectStatus, ProjectType, type ProjectFilters } from '~/types/project';

  const model = defineModel<ProjectFilters>({ default: () => ({}) });

  const { t } = useI18n();

  const statusOptions = Object.values(ProjectStatus).map((s) => ({
    label: t(`projects.status.${s}`),
    value: s,
  }));

  const typeOptions = Object.values(ProjectType).map((tp) => ({
    label: t(`projects.type.${tp}`),
    value: tp,
  }));

  const clearFilters = () => {
    model.value = {};
  };
</script>

<template>
  <div class="flex flex-wrap items-center gap-3">
    <USelectMenu
      v-model="model.status"
      :options="statusOptions"
      :placeholder="t('projects.filter_status')"
      value-attribute="value"
      option-attribute="label"
      class="w-44"
    />

    <USelectMenu
      v-model="model.type"
      :options="typeOptions"
      :placeholder="t('projects.filter_type')"
      value-attribute="value"
      option-attribute="label"
      class="w-44"
    />

    <UInput v-model="model.city" :placeholder="t('projects.filter_city')" class="w-44" />

    <UButton
      v-if="model.status || model.type || model.city"
      color="neutral"
      variant="ghost"
      size="sm"
      :label="t('common.clear')"
      @click="clearFilters"
    />
  </div>
</template>
