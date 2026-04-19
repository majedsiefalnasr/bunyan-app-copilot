<script setup lang="ts">
  import type { ProjectFormData } from '~/types/project';

  defineProps<{
    data: Partial<ProjectFormData>;
  }>();

  const { t } = useI18n();
</script>

<template>
  <div class="space-y-4">
    <h3 class="text-lg font-medium">{{ t('projects.review_title', 'مراجعة البيانات') }}</h3>
    <dl class="grid grid-cols-1 gap-3 md:grid-cols-2">
      <div>
        <dt class="text-sm text-gray-500">{{ t('projects.name_ar') }}</dt>
        <dd>{{ data.name_ar || '—' }}</dd>
      </div>
      <div>
        <dt class="text-sm text-gray-500">{{ t('projects.name_en') }}</dt>
        <dd>{{ data.name_en || '—' }}</dd>
      </div>
      <div>
        <dt class="text-sm text-gray-500">{{ t('projects.type_label') }}</dt>
        <dd>{{ data.type ? t(`projects.type.${data.type}`) : '—' }}</dd>
      </div>
      <div>
        <dt class="text-sm text-gray-500">{{ t('projects.city') }}</dt>
        <dd>{{ data.city || '—' }}</dd>
      </div>
      <div v-if="data.district">
        <dt class="text-sm text-gray-500">{{ t('projects.district') }}</dt>
        <dd>{{ data.district }}</dd>
      </div>
      <div v-if="data.budget_estimated">
        <dt class="text-sm text-gray-500">{{ t('projects.budget_estimated') }}</dt>
        <dd>{{ data.budget_estimated }}</dd>
      </div>
      <div v-if="data.start_date">
        <dt class="text-sm text-gray-500">{{ t('projects.start_date') }}</dt>
        <dd>{{ data.start_date }}</dd>
      </div>
      <div v-if="data.end_date">
        <dt class="text-sm text-gray-500">{{ t('projects.end_date') }}</dt>
        <dd>{{ data.end_date }}</dd>
      </div>
      <div v-if="data.description" class="md:col-span-2">
        <dt class="text-sm text-gray-500">{{ t('projects.description') }}</dt>
        <dd class="whitespace-pre-wrap">{{ data.description }}</dd>
      </div>
    </dl>
  </div>
</template>
