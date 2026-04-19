<script setup lang="ts">
  import type { Project } from '~/types/project';

  defineProps<{
    project: Project;
  }>();

  const { t } = useI18n();

  const tabItems = computed(() => [
    { label: t('projects.tab_overview', 'نظرة عامة'), slot: 'overview' as const },
    { label: t('projects.phases'), slot: 'phases' as const },
    { label: t('projects.tab_tasks', 'المهام'), slot: 'tasks' as const },
    { label: t('projects.tab_team', 'الفريق'), slot: 'team' as const },
    { label: t('projects.tab_documents', 'المستندات'), slot: 'documents' as const },
    { label: t('projects.tab_timeline', 'الجدول الزمني'), slot: 'timeline' as const },
  ]);
</script>

<template>
  <UTabs :items="tabItems" :lazy="true">
    <template #overview>
      <div class="mt-4">
        <OverviewTab :project="project" />
      </div>
    </template>
    <template #phases>
      <div class="mt-4">
        <PhasesTab :project-id="project.id" />
      </div>
    </template>
    <template #tasks>
      <div class="mt-4">
        <PlaceholderTab />
      </div>
    </template>
    <template #team>
      <div class="mt-4">
        <PlaceholderTab />
      </div>
    </template>
    <template #documents>
      <div class="mt-4">
        <PlaceholderTab />
      </div>
    </template>
    <template #timeline>
      <div class="mt-4">
        <TimelineTab :project-id="project.id" />
      </div>
    </template>
  </UTabs>
</template>
