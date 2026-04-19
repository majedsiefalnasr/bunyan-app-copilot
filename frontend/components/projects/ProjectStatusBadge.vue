<script setup lang="ts">
  import { ProjectStatus } from '~/types/project';

  const props = defineProps<{
    status: ProjectStatus;
  }>();

  const { t } = useI18n();

  const colorMap: Record<ProjectStatus, string> = {
    [ProjectStatus.Draft]: 'neutral',
    [ProjectStatus.Planning]: 'info',
    [ProjectStatus.InProgress]: 'warning',
    [ProjectStatus.OnHold]: 'error',
    [ProjectStatus.Completed]: 'success',
    [ProjectStatus.Closed]: 'error',
  };

  const label = computed(() => t(`projects.status.${props.status}`));
  const color = computed(() => colorMap[props.status] ?? 'neutral');
</script>

<template>
  <UBadge :color="color" variant="subtle" size="sm">
    {{ label }}
  </UBadge>
</template>
