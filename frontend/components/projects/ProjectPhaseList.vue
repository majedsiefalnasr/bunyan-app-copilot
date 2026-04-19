<script setup lang="ts">
  import type { ProjectPhase, PhaseFormData } from '~/types/project';

  const props = defineProps<{
    phases: ProjectPhase[];
    projectId: number;
    isLoading?: boolean;
  }>();

  const emit = defineEmits<{
    create: [data: PhaseFormData];
    update: [phaseId: number, data: Partial<PhaseFormData>];
    delete: [phaseId: number];
  }>();

  const { t, locale } = useI18n();

  const showCreateModal = ref(false);

  const phaseForm = reactive<PhaseFormData>({
    name_ar: '',
    name_en: '',
    sort_order: props.phases.length + 1,
    start_date: undefined,
    end_date: undefined,
    completion_percentage: 0,
  });

  const phaseName = (phase: ProjectPhase) =>
    locale.value === 'ar' ? phase.name_ar : phase.name_en;

  const handleCreate = () => {
    emit('create', { ...phaseForm });
    showCreateModal.value = false;
    phaseForm.name_ar = '';
    phaseForm.name_en = '';
    phaseForm.sort_order = props.phases.length + 2;
  };
</script>

<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-medium">{{ t('projects.phases') }}</h3>
      <UButton
        size="sm"
        :label="t('projects.add_phase')"
        icon="i-heroicons-plus"
        @click="showCreateModal = true"
      />
    </div>

    <div v-if="phases.length === 0" class="py-8 text-center text-gray-500">
      {{ t('projects.no_phases') }}
    </div>

    <div v-else class="space-y-3">
      <UCard v-for="phase in phases" :key="phase.id" class="relative">
        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-medium">{{ phaseName(phase) }}</h4>
            <div class="mt-1 flex items-center gap-3 text-sm text-gray-500">
              <UBadge
                :color="
                  phase.status === 'completed'
                    ? 'success'
                    : phase.status === 'in_progress'
                      ? 'warning'
                      : 'neutral'
                "
                variant="subtle"
                size="xs"
              >
                {{ t(`projects.phase_status.${phase.status}`) }}
              </UBadge>
              <span>{{ phase.completion_percentage }}%</span>
            </div>
          </div>

          <UDropdown
            :items="[
              [
                {
                  label: t('common.delete'),
                  icon: 'i-heroicons-trash',
                  click: () => emit('delete', phase.id),
                },
              ],
            ]"
          >
            <UButton
              icon="i-heroicons-ellipsis-vertical"
              color="neutral"
              variant="ghost"
              size="xs"
            />
          </UDropdown>
        </div>

        <UProgress :value="phase.completion_percentage" class="mt-2" size="sm" />
      </UCard>
    </div>

    <UModal v-model="showCreateModal">
      <UCard :ui="{ header: { padding: 'p-4' }, body: { padding: 'p-4' } }">
        <template #header>
          <h3 class="text-lg font-medium">{{ t('projects.add_phase') }}</h3>
        </template>

        <form class="space-y-4" @submit.prevent="handleCreate">
          <UFormGroup :label="t('projects.phase_name_ar')" required>
            <UInput v-model="phaseForm.name_ar" dir="rtl" />
          </UFormGroup>
          <UFormGroup :label="t('projects.phase_name_en')" required>
            <UInput v-model="phaseForm.name_en" dir="ltr" />
          </UFormGroup>
          <UFormGroup :label="t('projects.sort_order')">
            <UInput v-model.number="phaseForm.sort_order" type="number" min="1" />
          </UFormGroup>

          <div class="flex justify-end gap-2">
            <UButton
              color="neutral"
              variant="ghost"
              :label="t('common.cancel')"
              @click="showCreateModal = false"
            />
            <UButton type="submit" :label="t('common.save')" :loading="isLoading" />
          </div>
        </form>
      </UCard>
    </UModal>
  </div>
</template>
