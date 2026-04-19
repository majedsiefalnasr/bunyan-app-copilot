<script setup lang="ts">
  definePageMeta({
    layout: 'dashboard',
    middleware: ['auth', 'role'],
    requiredRole: 'admin',
  });

  const { t, locale } = useI18n();
  const route = useRoute();
  const projectStore = useProjectStore();

  const projectId = computed(() => Number(route.params.id));

  const projectName = computed(() => {
    if (!projectStore.selectedProject) return '';
    return locale.value === 'ar'
      ? projectStore.selectedProject.name_ar
      : projectStore.selectedProject.name_en;
  });

  onMounted(() => {
    projectStore.loadProject(projectId.value);
  });
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <UButton
          icon="i-heroicons-arrow-right"
          color="neutral"
          variant="ghost"
          @click="navigateTo('/admin/projects')"
        />
        <h1 class="text-2xl font-semibold tracking-tight">{{ projectName }}</h1>
        <ProjectStatusBadge
          v-if="projectStore.selectedProject"
          :status="projectStore.selectedProject.status"
        />
      </div>

      <div class="flex items-center gap-2">
        <StatusTransitionControl
          v-if="projectStore.selectedProject"
          :project-id="projectId"
          :current-status="projectStore.selectedProject.status"
          :updated-at="projectStore.selectedProject.updated_at"
        />
        <UButton
          :label="t('common.edit')"
          icon="i-heroicons-pencil"
          variant="outline"
          @click="navigateTo(`/admin/projects/${projectId}/edit`)"
        />
      </div>
    </div>

    <div v-if="projectStore.isLoading" class="flex justify-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="size-8 animate-spin text-gray-400" />
    </div>

    <ProjectDetailTabs
      v-else-if="projectStore.selectedProject"
      :project="projectStore.selectedProject"
    />
  </div>
</template>
