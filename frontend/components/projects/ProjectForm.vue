<script setup lang="ts">
  import { ProjectType, type ProjectFormData } from '~/types/project';

  const props = defineProps<{
    initialData?: Partial<ProjectFormData>;
    isEdit?: boolean;
    isLoading?: boolean;
  }>();

  const emit = defineEmits<{
    submit: [data: ProjectFormData];
    cancel: [];
  }>();

  const { t } = useI18n();

  const form = reactive<ProjectFormData>({
    owner_id: props.initialData?.owner_id ?? 0,
    name_ar: props.initialData?.name_ar ?? '',
    name_en: props.initialData?.name_en ?? '',
    description: props.initialData?.description ?? '',
    city: props.initialData?.city ?? '',
    district: props.initialData?.district ?? '',
    type: props.initialData?.type ?? ProjectType.Residential,
    budget_estimated: props.initialData?.budget_estimated ?? undefined,
    start_date: props.initialData?.start_date ?? undefined,
    end_date: props.initialData?.end_date ?? undefined,
  });

  const typeOptions = Object.values(ProjectType).map((tp) => ({
    label: t(`projects.type.${tp}`),
    value: tp,
  }));

  const handleSubmit = () => {
    emit('submit', { ...form });
  };
</script>

<template>
  <form class="space-y-6" @submit.prevent="handleSubmit">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <UFormGroup :label="t('projects.name_ar')" required>
        <UInput v-model="form.name_ar" dir="rtl" :placeholder="t('projects.name_ar')" />
      </UFormGroup>

      <UFormGroup :label="t('projects.name_en')" required>
        <UInput v-model="form.name_en" dir="ltr" :placeholder="t('projects.name_en')" />
      </UFormGroup>
    </div>

    <UFormGroup :label="t('projects.description')">
      <UTextarea v-model="form.description" :rows="3" />
    </UFormGroup>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <UFormGroup :label="t('projects.type_label')" required>
        <USelectMenu
          v-model="form.type"
          :options="typeOptions"
          value-attribute="value"
          option-attribute="label"
        />
      </UFormGroup>

      <UFormGroup :label="t('projects.city')" required>
        <UInput v-model="form.city" :placeholder="t('projects.city')" />
      </UFormGroup>

      <UFormGroup :label="t('projects.district')">
        <UInput v-model="form.district" :placeholder="t('projects.district')" />
      </UFormGroup>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <UFormGroup :label="t('projects.budget_estimated')">
        <UInput v-model.number="form.budget_estimated" type="number" min="0" step="0.01" />
      </UFormGroup>

      <UFormGroup :label="t('projects.start_date')">
        <UInput v-model="form.start_date" type="date" />
      </UFormGroup>

      <UFormGroup :label="t('projects.end_date')">
        <UInput v-model="form.end_date" type="date" />
      </UFormGroup>
    </div>

    <div class="flex items-center justify-end gap-3">
      <UButton
        color="neutral"
        variant="ghost"
        :label="t('common.cancel')"
        @click="emit('cancel')"
      />
      <UButton
        type="submit"
        :label="isEdit ? t('common.save') : t('projects.create')"
        :loading="isLoading"
      />
    </div>
  </form>
</template>
