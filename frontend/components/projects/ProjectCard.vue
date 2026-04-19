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

  const projectName = (project: Project) =>
    locale.value === 'ar' ? project.name_ar : project.name_en;
</script>

<template>
  <UCard
    class="cursor-pointer transition-shadow hover:shadow-md"
    @click="navigateTo(`/projects/${project.id}`)"
  >
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-base font-medium truncate">
          {{ projectName(project) }}
        </h3>
        <ProjectStatusBadge :status="project.status" />
      </div>
    </template>

    <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
      <div class="flex items-center gap-2">
        <UIcon name="i-heroicons-map-pin" class="size-4 shrink-0" />
        <span class="truncate">{{ project.city }}</span>
      </div>

      <div class="flex items-center gap-2">
        <UIcon name="i-heroicons-banknotes" class="size-4 shrink-0" />
        <span>{{ formatCurrency(project.budget_estimated) }}</span>
      </div>

      <div v-if="project.start_date || project.end_date" class="flex items-center gap-2">
        <UIcon name="i-heroicons-calendar" class="size-4 shrink-0" />
        <span>{{ project.start_date ?? '—' }} → {{ project.end_date ?? '—' }}</span>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-between text-xs text-gray-500">
        <UBadge variant="subtle" size="xs" color="neutral">
          {{ t(`projects.type.${project.type}`) }}
        </UBadge>
        <span v-if="project.phases_count !== undefined">
          {{ t('projects.phases_count', { count: project.phases_count }) }}
        </span>
      </div>
    </template>
  </UCard>
</template>
