<script setup lang="ts">
  import type { FormSubmitEvent } from '#ui/types';
  import { z } from 'zod';
  import type { Category, CategoryFormData } from '~/types';

  interface Props {
    isOpen: boolean;
    category?: Category | null;
    parentCategories?: Category[];
    onClose: () => void;
    onSubmit: (data: CategoryFormData & { id?: number; version?: number }) => Promise<void>;
  }

  const props = withDefaults(defineProps<Props>(), {
    category: null,
    parentCategories: () => [],
  });

  const { t } = useI18n();
  const isSubmitting = ref(false);

  // Validation schema
  const validationSchema = z.object({
    name_ar: z
      .string()
      .min(2, t('validation.minChars', { count: 2 }))
      .max(100),
    name_en: z
      .string()
      .min(2, t('validation.minChars', { count: 2 }))
      .max(100),
    parent_id: z.number().nullable(),
    icon: z.string().max(50).optional().default(''),
    is_active: z.boolean().default(true),
  });

  type Schema = z.output<typeof validationSchema>;

  type FormState = {
    name_ar: string;
    name_en: string;
    parent_id: number | null;
    icon: string;
    is_active: boolean;
  };

  // Form state using reactive for proper v-model binding
  const formState = reactive<FormState>({
    name_ar: '',
    name_en: '',
    parent_id: null,
    icon: '',
    is_active: true,
  });

  const version = ref(0);

  /**
   * Initialize form with category data or reset for create
   */
  const initializeForm = () => {
    if (props.category) {
      formState.name_ar = props.category.name_ar;
      formState.name_en = props.category.name_en;
      formState.parent_id = props.category.parent_id;
      formState.icon = props.category.icon || '';
      formState.is_active = props.category.is_active;
      version.value = props.category.version;
    } else {
      formState.name_ar = '';
      formState.name_en = '';
      formState.parent_id = null;
      formState.icon = '';
      formState.is_active = true;
      version.value = 0;
    }
  };

  /**
   * Handle form submission
   */
  const handleSubmit = async (_event: FormSubmitEvent<Schema>) => {
    try {
      isSubmitting.value = true;

      const submitData: CategoryFormData & { id?: number; version?: number } = {
        name_ar: formState.name_ar,
        name_en: formState.name_en,
        parent_id: formState.parent_id,
        icon: formState.icon || undefined,
        is_active: formState.is_active,
      };

      // Add ID and version if editing
      if (props.category) {
        submitData.id = props.category.id;
        submitData.version = version.value;
      }

      await props.onSubmit(submitData);
      handleClose();
    } catch (error) {
      console.error('Form submission error:', error);
    } finally {
      isSubmitting.value = false;
    }
  };

  const handleClose = () => {
    initializeForm();
    props.onClose();
  };

  // Watch for prop changes
  watch(
    () => props.isOpen,
    (newVal) => {
      if (newVal) {
        initializeForm();
      }
    }
  );

  watch(
    () => props.category,
    () => {
      initializeForm();
    },
    { deep: true, immediate: true }
  );

  const mode = computed(() => (props.category ? 'edit' : 'create'));
  const title = computed(() =>
    mode.value === 'edit' ? t('categories.editCategory') : t('categories.addCategory')
  );

  // Expose for testing
  defineExpose({
    validationSchema,
    formState,
    version,
    initializeForm,
  });
</script>

<template>
  <UModal
    :model-value="isOpen"
    size="md"
    @update:model-value="(value: boolean) => !value && handleClose()"
  >
    <UCard>
      <template #header>
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
          <UButton
            color="neutral"
            variant="ghost"
            size="sm"
            icon="i-heroicons-x-mark-20-solid"
            @click="handleClose"
          />
        </div>
      </template>

      <!-- Form Content -->
      <UForm :schema="validationSchema" :state="formState" class="space-y-4" @submit="handleSubmit">
        <!-- Arabic Name -->
        <UFormGroup :label="$t('categories.nameAr')" name="name_ar">
          <UInput
            v-model="formState.name_ar"
            :placeholder="$t('categories.enterNameAr')"
            size="md"
            :disabled="isSubmitting.value"
          /><!-- @ts-expect-error Nuxt UI type -->
        </UFormGroup>

        <!-- English Name -->
        <UFormGroup :label="$t('categories.nameEn')" name="name_en">
          <UInput
            v-model="formState.name_en"
            dir="ltr"
            :placeholder="$t('categories.enterNameEn')"
            size="md"
            :disabled="isSubmitting.value"
          /><!-- @ts-expect-error Nuxt UI type -->
        </UFormGroup>

        <!-- Parent Category -->
        <UFormGroup :label="$t('categories.parentCategory')" name="parent_id">
          <USelectMenu
            v-model="formState.parent_id"
            :options="parentCategories"
            option-attribute="name_ar"
            :placeholder="$t('categories.selectParent')"
            :disabled="isSubmitting.value"
            nullable
            searchable
            value-attribute="id"
          /><!-- @ts-expect-error Nuxt UI type -->
        </UFormGroup>

        <!-- Icon (optional) -->
        <UFormGroup :label="$t('categories.icon')" name="icon">
          <UInput
            v-model="formState.icon"
            :placeholder="$t('categories.enterIcon')"
            size="md"
            :disabled="isSubmitting.value"
          /><!-- @ts-expect-error Nuxt UI type -->
        </UFormGroup>

        <!-- Active Status -->
        <UFormGroup :label="$t('categories.isActive')" name="is_active">
          <div class="flex items-center">
            <UCheckbox
              v-model="formState.is_active"
              :disabled="isSubmitting.value"
            /><!-- @ts-expect-error Nuxt UI type -->
          </div>
        </UFormGroup>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4">
          <UButton color="neutral" :disabled="isSubmitting.value" @click="handleClose"
            ><!-- @ts-expect-error Nuxt UI type -->
            {{ $t('common.cancel') }}
          </UButton>
          <UButton type="submit" :loading="isSubmitting.value"
            ><!-- @ts-expect-error Nuxt UI type -->
            {{ mode === 'edit' ? $t('common.update') : $t('common.create') }}
          </UButton>
        </div>
      </UForm>
    </UCard>
  </UModal>
</template>
