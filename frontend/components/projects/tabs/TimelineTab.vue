<script setup lang="ts">
  import type { TimelineData } from '~/types/project';

  const props = defineProps<{
    projectId: number;
  }>();

  const { t, locale } = useI18n();
  const { fetchTimeline } = useProjectPhases();

  const timeline = ref<TimelineData | null>(null);
  const isLoading = ref(false);

  const phaseName = (phase: { name_ar: string; name_en: string }) =>
    locale.value === 'ar' ? phase.name_ar : phase.name_en;

  const statusColor = (status: string) => {
    const map: Record<string, string> = {
      pending: 'bg-gray-200',
      in_progress: 'bg-yellow-400',
      completed: 'bg-green-500',
    };
    return map[status] ?? 'bg-gray-200';
  };

  onMounted(async () => {
    try {
      isLoading.value = true;
      timeline.value = await fetchTimeline(props.projectId);
    } catch {
      // Error handled silently — empty state shown
    } finally {
      isLoading.value = false;
    }
  });
</script>

<template>
  <div>
    <div v-if="isLoading" class="flex justify-center py-8">
      <UIcon name="i-heroicons-arrow-path" class="size-6 animate-spin text-gray-400" />
    </div>

    <div
      v-else-if="!timeline || timeline.phases.length === 0"
      class="py-8 text-center text-gray-500"
    >
      {{ t('projects.no_phases') }}
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="phase in timeline.phases"
        :key="phase.id"
        class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
      >
        <div class="mb-2 flex items-center justify-between">
          <span class="font-medium">{{ phaseName(phase) }}</span>
          <span class="text-xs text-gray-500">{{ phase.completion_percentage }}%</span>
        </div>
        <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
          <div
            :class="statusColor(phase.status)"
            class="h-full rounded-full transition-all"
            :style="{ width: `${phase.completion_percentage}%` }"
          />
        </div>
        <div v-if="phase.start_date || phase.end_date" class="mt-1 text-xs text-gray-400">
          {{ phase.start_date ?? '—' }} → {{ phase.end_date ?? '—' }}
        </div>
      </div>
    </div>
  </div>
</template>
