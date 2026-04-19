<script setup lang="ts">
  import { ProjectType, type ProjectFormData } from '~/types/project';

  const emit = defineEmits<{
    submit: [data: ProjectFormData];
  }>();

  const { t } = useI18n();
  const isSubmitting = ref(false);

  const currentStep = ref(0);
  const totalSteps = 4;

  const formData = reactive<Partial<ProjectFormData>>({
    owner_id: 0,
    name_ar: '',
    name_en: '',
    description: '',
    city: '',
    district: '',
    type: ProjectType.Residential,
  });

  const stepTitles = computed(() => [
    t('projects.wizard_basic', 'المعلومات الأساسية'),
    t('projects.wizard_location', 'الموقع'),
    t('projects.wizard_budget', 'الميزانية والتواريخ'),
    t('projects.wizard_review', 'المراجعة'),
  ]);

  const canProceed = computed(() => {
    if (currentStep.value === 0) {
      return !!(formData.name_ar && formData.name_en && formData.type);
    }
    if (currentStep.value === 1) {
      return !!formData.city;
    }
    return true;
  });

  const next = () => {
    if (currentStep.value < totalSteps - 1) currentStep.value++;
  };

  const prev = () => {
    if (currentStep.value > 0) currentStep.value--;
  };

  const handleSubmit = () => {
    emit('submit', formData as ProjectFormData);
  };
</script>

<template>
  <div class="space-y-6">
    <!-- Step indicator -->
    <div class="flex items-center justify-center gap-2">
      <template v-for="(title, i) in stepTitles" :key="i">
        <div
          class="flex size-8 items-center justify-center rounded-full text-sm font-medium"
          :class="i <= currentStep ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-500'"
        >
          {{ i + 1 }}
        </div>
        <div
          v-if="i < totalSteps - 1"
          class="h-px w-8"
          :class="i < currentStep ? 'bg-primary-500' : 'bg-gray-200'"
        />
      </template>
    </div>

    <h2 class="text-center text-lg font-medium">{{ stepTitles[currentStep] }}</h2>

    <!-- Steps -->
    <StepBasicInfo v-if="currentStep === 0" v-model="formData" />
    <StepLocation v-else-if="currentStep === 1" v-model="formData" />
    <StepBudget v-else-if="currentStep === 2" v-model="formData" />
    <StepReview v-else-if="currentStep === 3" :data="formData" />

    <!-- Navigation -->
    <div class="flex items-center justify-between">
      <UButton
        v-if="currentStep > 0"
        color="neutral"
        variant="ghost"
        :label="t('projects.wizard_back', 'السابق')"
        icon="i-heroicons-arrow-right"
        @click="prev"
      />
      <div v-else />

      <UButton
        v-if="currentStep < totalSteps - 1"
        :label="t('projects.wizard_next', 'التالي')"
        :disabled="!canProceed"
        trailing-icon="i-heroicons-arrow-left"
        @click="next"
      />
      <UButton
        v-else
        :label="t('projects.create')"
        :loading="isSubmitting"
        :disabled="!canProceed"
        @click="handleSubmit"
      />
    </div>
  </div>
</template>
