<script setup lang="ts">
  import type { Project } from '~/types/project';

  defineProps<{
    project: Project;
  }>();

  const { t, locale } = useI18n();

  const formatCurrency = (value: string | null) => {
    if (!value) return '—';
    return new Intl.NumberFormat(locale.value === 'ar' ? 'ar-SA' : 'en-SA', {
      style: 'currency',
      currency: 'SAR',
    }).format(Number(value));
  };
</script>

<template>
  <UCard>
    <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div>
        <dt class="text-sm text-gray-500">{{ t('projects.name_ar') }}</dt>
        <dd dir="rtl">{{ project.name_ar }}</dd>
      </div>
      <div>
        <dt class="text-sm text-gray-500">{{ t('projects.name_en') }}</dt>
        <dd dir="ltr">{{ project.name_en }}</dd>
      </div>
      <div>
        <dt class="text-sm text-gray-500">{{ t('projects.type_label') }}</dt>
        <dd>{{ t(`projects.type.${project.type}`) }}</dd>
      </div>
      <div>
        <dt class="text-sm text-gray-500">{{ t('projects.city') }}</dt>
        <dd>{{ project.city }}</dd>
      </div>
      <div v-if="project.district">
        <dt class="text-sm text-gray-500">{{ t('projects.district') }}</dt>
        <dd>{{ project.district }}</dd>
      </div>
      <div v-if="project.budget_estimated">
        <dt class="text-sm text-gray-500">{{ t('projects.budget_estimated') }}</dt>
        <dd>{{ formatCurrency(project.budget_estimated) }}</dd>
      </div>
      <div v-if="project.budget_actual">
        <dt class="text-sm text-gray-500">
          {{ t('projects.budget_actual', 'الميزانية الفعلية') }}
        </dt>
        <dd>{{ formatCurrency(project.budget_actual) }}</dd>
      </div>
      <div v-if="project.start_date">
        <dt class="text-sm text-gray-500">{{ t('projects.start_date') }}</dt>
        <dd>{{ project.start_date }}</dd>
      </div>
      <div v-if="project.end_date">
        <dt class="text-sm text-gray-500">{{ t('projects.end_date') }}</dt>
        <dd>{{ project.end_date }}</dd>
      </div>
      <div v-if="project.owner" class="md:col-span-2">
        <dt class="text-sm text-gray-500">{{ t('projects.owner', 'المالك') }}</dt>
        <dd>{{ project.owner.name }} ({{ project.owner.email }})</dd>
      </div>
      <div v-if="project.description" class="md:col-span-2">
        <dt class="text-sm text-gray-500">{{ t('projects.description') }}</dt>
        <dd class="whitespace-pre-wrap">{{ project.description }}</dd>
      </div>
    </dl>
  </UCard>
</template>
