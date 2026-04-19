<script setup lang="ts">
  import type { ProjectFormData } from '~/types/project';

  definePageMeta({
    layout: 'dashboard',
    middleware: ['auth', 'role'],
    requiredRole: 'admin',
  });

  const { t } = useI18n();
  const projectStore = useProjectStore();
  const { notifySuccess, notifyError } = useNotification();
  const isSubmitting = ref(false);

  const handleSubmit = async (data: ProjectFormData) => {
    try {
      isSubmitting.value = true;
      await projectStore.create(data);
      notifySuccess(t('projects.created_success'));
      navigateTo('/admin/projects');
    } catch (err) {
      notifyError((err as Error).message);
    } finally {
      isSubmitting.value = false;
    }
  };
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <div class="flex items-center gap-3">
      <UButton
        icon="i-heroicons-arrow-right"
        color="neutral"
        variant="ghost"
        @click="navigateTo('/admin/projects')"
      />
      <h1 class="text-2xl font-semibold tracking-tight">{{ t('projects.create') }}</h1>
    </div>

    <UCard>
      <ProjectWizard @submit="handleSubmit" />
    </UCard>
  </div>
</template>
