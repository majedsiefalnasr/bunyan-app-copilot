<script setup lang="ts">
  import type { ProjectFilters as ProjectFiltersType } from '~/types/project';

  definePageMeta({
    layout: 'dashboard',
    middleware: ['auth', 'role'],
    requiredRole: 'admin',
  });

  const { t } = useI18n();
  const projectStore = useProjectStore();

  const filters = ref<ProjectFiltersType>({});

  watch(
    filters,
    () => {
      projectStore.setFilters(filters.value);
      projectStore.loadProjects(1);
    },
    { deep: true }
  );

  onMounted(() => {
    projectStore.loadProjects();
  });
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold tracking-tight">{{ t('projects.title') }}</h1>
      <UButton
        :label="t('projects.create')"
        icon="i-heroicons-plus"
        @click="navigateTo('/admin/projects/create')"
      />
    </div>

    <ProjectFilters v-model="filters" />

    <div v-if="projectStore.isLoading" class="flex justify-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="size-8 animate-spin text-gray-400" />
    </div>

    <div v-else-if="projectStore.projects.length === 0" class="py-12 text-center text-gray-500">
      {{ t('projects.empty') }}
    </div>

    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      <ProjectCard v-for="project in projectStore.projects" :key="project.id" :project="project" />
    </div>

    <div v-if="projectStore.lastPage > 1" class="flex justify-center">
      <UPagination
        :model-value="projectStore.currentPage"
        :total="projectStore.total"
        :page-count="15"
        @update:model-value="projectStore.loadProjects($event)"
      />
    </div>
  </div>
</template>
