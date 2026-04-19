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

  // Form state - use individual refs for proper TypeScript typing with v-model
  const nameAr = ref('');
  const nameEn = ref('');
  const parentId = ref<number | null>(null);
  const icon = ref('');
  const isActive = ref(true);

  const version = ref(0);

  /**
   * Initialize form with category data or reset for create
   */
  const initializeForm = () => {
    if (props.category) {
      nameAr.value = props.category.name_ar;
      nameEn.value = props.category.name_en;
      parentId.value = props.category.parent_id;
      icon.value = props.category.icon || '';
      isActive.value = props.category.is_active;
      version.value = props.category.version;
    } else {
      nameAr.value = '';
      nameEn.value = '';
      parentId.value = null;
      icon.value = '';
      isActive.value = true;
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
        name_ar: nameAr.value,
        name_en: nameEn.value,
        parent_id: parentId.value,
        icon: icon.value || undefined,
        is_active: isActive.value,
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
    { deep: true }
  );

  const mode = computed(() => (props.category ? 'edit' : 'create'));
  const title = computed(() =>
    mode.value === 'edit' ? t('categories.editCategory') : t('categories.addCategory')
  );
</script>

<template>
  <UModal :model-value="isOpen" size="md" @update:model-value="isOpen ? null : handleClose()">
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
      <UForm
        :schema="validationSchema"
        :state="{
          name_ar: nameAr.value,
          name_en: nameEn.value,
          parent_id: parentId.value,
          icon: icon.value,
          is_active: isActive.value,
        }"
        class="space-y-4"
        @submit="handleSubmit"
      >
        <!-- Arabic Name -->
        <UFormGroup :label="$t('categories.nameAr')" name="name_ar">
          <UInput
            v-model="nameAr"
            :placeholder="$t('categories.enterNameAr')"
            size="md"
            :disabled="isSubmitting.value"
          />
        </UFormGroup>

        <!-- English Name -->
        <UFormGroup :label="$t('categories.nameEn')" name="name_en">
          <UInput
            v-model="nameEn"
            dir="ltr"
            :placeholder="$t('categories.enterNameEn')"
            size="md"
            :disabled="isSubmitting.value"
          />
        </UFormGroup>

        <!-- Parent Category -->
        <UFormGroup :label="$t('categories.parentCategory')" name="parent_id">
          <USelectMenu
            v-model="parentId"
            :options="parentCategories"
            option-attribute="name_ar"
            :placeholder="$t('categories.selectParent')"
            :disabled="isSubmitting.value"
            nullable
            searchable
            value-attribute="id"
          />
        </UFormGroup>

        <!-- Icon (optional) -->
        <UFormGroup :label="$t('categories.icon')" name="icon">
          <UInput
            v-model="icon"
            :placeholder="$t('categories.enterIcon')"
            size="md"
            :disabled="isSubmitting.value"
          />
        </UFormGroup>

        <!-- Active Status -->
        <UFormGroup :label="$t('categories.isActive')" name="is_active">
          <div class="flex items-center">
            <UCheckbox v-model="isActive" :disabled="isSubmitting.value" />
          </div>
        </UFormGroup>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4">
          <UButton color="neutral" :disabled="isSubmitting.value" @click="handleClose">
            {{ $t('common.cancel') }}
          </UButton>
          <UButton type="submit" :loading="isSubmitting.value">
            {{ mode === 'edit' ? $t('common.update') : $t('common.create') }}
          </UButton>
        </div>
      </UForm>
    </UCard>
  </UModal>
</template>
