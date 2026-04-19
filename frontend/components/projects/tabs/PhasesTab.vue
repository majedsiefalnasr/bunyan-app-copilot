<script setup lang="ts">
  import type { ProjectPhase } from '~/types/project';

  defineProps<{
    projectId: number;
  }>();

  const { t } = useI18n();
  const { createPhase, deletePhase } = useProjectPhases();
  const { notifySuccess } = useNotification();

  const phases = ref<ProjectPhase[]>([]);
  const isLoading = ref(false);

  onMounted(async () => {
    // Phases loaded by parent — accept as prop or load here
  });
</script>

<template>
  <ProjectPhaseList
    :phases="phases"
    :project-id="projectId"
    :is-loading="isLoading"
    @create="
      async (data) => {
        await createPhase(projectId, data);
        notifySuccess(t('projects.phase_created'));
      }
    "
    @delete="
      async (phaseId) => {
        await deletePhase(projectId, phaseId);
        notifySuccess(t('projects.phase_deleted'));
      }
    "
  />
</template>
