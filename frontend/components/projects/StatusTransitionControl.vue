<script setup lang="ts">
  import { ProjectStatus } from '~/types/project';

  const props = defineProps<{
    projectId: number;
    currentStatus: ProjectStatus;
    updatedAt: string;
  }>();

  const { t } = useI18n();
  const projectStore = useProjectStore();
  const { notifySuccess, notifyError } = useNotification();

  const showConfirm = ref(false);
  const pendingStatus = ref<ProjectStatus | null>(null);
  const isTransitioning = ref(false);

  const allowedTransitions: Record<string, ProjectStatus[]> = {
    [ProjectStatus.Draft]: [ProjectStatus.Planning],
    [ProjectStatus.Planning]: [ProjectStatus.InProgress],
    [ProjectStatus.InProgress]: [ProjectStatus.OnHold, ProjectStatus.Completed],
    [ProjectStatus.OnHold]: [ProjectStatus.InProgress],
    [ProjectStatus.Completed]: [ProjectStatus.Closed],
    [ProjectStatus.Closed]: [],
  };

  const transitions = computed(() =>
    (allowedTransitions[props.currentStatus] ?? []).map((s) => ({
      label: t(`projects.status.${s}`),
      value: s,
    }))
  );

  const initiateTransition = (status: ProjectStatus) => {
    pendingStatus.value = status;
    showConfirm.value = true;
  };

  const confirmTransition = async () => {
    if (!pendingStatus.value) return;
    try {
      isTransitioning.value = true;
      await projectStore.transitionStatus(props.projectId, pendingStatus.value);
      notifySuccess(t('projects.status_updated', 'تم تحديث الحالة'));
      showConfirm.value = false;
    } catch (err) {
      notifyError((err as Error).message);
    } finally {
      isTransitioning.value = false;
    }
  };
</script>

<template>
  <div v-if="transitions.length > 0">
    <UDropdown
      :items="
        transitions.map((tr) => [{ label: tr.label, click: () => initiateTransition(tr.value) }])
      "
    >
      <UButton
        size="sm"
        variant="outline"
        :label="t('projects.change_status', 'تغيير الحالة')"
        icon="i-heroicons-arrow-path"
      />
    </UDropdown>

    <UModal v-model="showConfirm">
      <UCard>
        <template #header>
          <h3 class="text-lg font-medium">
            {{ t('projects.confirm_transition', 'تأكيد تغيير الحالة') }}
          </h3>
        </template>
        <p>
          {{ t('projects.transition_message', 'هل أنت متأكد من تغيير حالة المشروع إلى') }}
          <strong>{{ pendingStatus ? t(`projects.status.${pendingStatus}`) : '' }}</strong
          >?
        </p>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton
              color="neutral"
              variant="ghost"
              :label="t('common.cancel')"
              @click="showConfirm = false"
            />
            <UButton
              :label="t('common.save')"
              :loading="isTransitioning"
              @click="confirmTransition"
            />
          </div>
        </template>
      </UCard>
    </UModal>
  </div>
</template>
