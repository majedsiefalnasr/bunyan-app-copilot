<script setup lang="ts">
  import type { ProjectFormData } from '~/types/project';

  definePageMeta({
    layout: 'dashboard',
    middleware: ['auth', 'role'],
    requiredRole: 'admin',
  });

  const { t, locale } = useI18n();
  const route = useRoute();
  const projectStore = useProjectStore();
  const { notifySuccess, notifyError } = useNotification();
  const isSubmitting = ref(false);

  const projectId = computed(() => Number(route.params.id));

  const initialData = computed(() => {
    const p = projectStore.selectedProject;
    if (!p) return undefined;
    return {
      owner_id: p.owner_id,
      name_ar: p.name_ar,
      name_en: p.name_en,
      description: p.description ?? '',
      city: p.city,
      district: p.district ?? '',
      type: p.type,
      budget_estimated: p.budget_estimated ? Number(p.budget_estimated) : undefined,
      start_date: p.start_date ?? undefined,
      end_date: p.end_date ?? undefined,
    };
  });

  const projectName = computed(() => {
    if (!projectStore.selectedProject) return '';
    return locale.value === 'ar'
      ? projectStore.selectedProject.name_ar
      : projectStore.selectedProject.name_en;
  });

  const handleSubmit = async (data: ProjectFormData) => {
    try {
      isSubmitting.value = true;
      await projectStore.update(projectId.value, data);
      notifySuccess(t('projects.updated_success'));
      navigateTo(`/admin/projects/${projectId.value}`);
    } catch (err) {
      notifyError((err as Error).message);
    } finally {
      isSubmitting.value = false;
    }
  };

  onMounted(() => {
    if (!projectStore.selectedProject || projectStore.selectedProject.id !== projectId.value) {
      projectStore.loadProject(projectId.value);
    }
  });
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center gap-3">
      <UButton
        icon="i-heroicons-arrow-right"
        color="neutral"
        variant="ghost"
        @click="navigateTo(`/admin/projects/${projectId}`)"
      />
      <h1 class="text-2xl font-semibold tracking-tight">
        {{ t('projects.edit') }}: {{ projectName }}
      </h1>
    </div>

    <UCard>
      <ProjectForm
        v-if="initialData"
        :initial-data="initialData"
        :is-edit="true"
        :is-loading="isSubmitting"
        @submit="handleSubmit"
        @cancel="navigateTo(`/admin/projects/${projectId}`)"
      />
      <div v-else class="flex justify-center py-8">
        <UIcon name="i-heroicons-arrow-path" class="size-8 animate-spin text-gray-400" />
      </div>
    </UCard>
  </div>
</template>
