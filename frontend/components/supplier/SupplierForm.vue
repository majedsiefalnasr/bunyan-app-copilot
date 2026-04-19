<script setup lang="ts">
  import { z } from 'zod';
  import type {
    StoreSupplierPayload,
    SupplierProfile,
    UpdateSupplierPayload,
  } from '~~/types/supplier';

  const props = defineProps<{
    supplier?: SupplierProfile;
    loading?: boolean;
  }>();

  const emit = defineEmits<{
    submit: [payload: StoreSupplierPayload | UpdateSupplierPayload];
  }>();

  const { t } = useI18n();

  const schema = z.object({
    company_name_ar: z.string().min(1, t('validation.required')).max(255),
    company_name_en: z.string().min(1, t('validation.required')).max(255),
    commercial_reg: z.string().min(1, t('validation.required')).max(100),
    phone: z.string().regex(/^05\d{8}$/, t('suppliers.validation.phone_format')),
    city: z.string().min(1, t('validation.required')).max(100),
    tax_number: z.string().max(50).nullable().optional(),
    district: z.string().max(255).nullable().optional(),
    address: z.string().max(500).nullable().optional(),
    description_ar: z.string().nullable().optional(),
    description_en: z.string().nullable().optional(),
    logo: z.string().url(t('validation.url')).nullable().optional(),
    website: z.string().url(t('validation.url')).nullable().optional(),
  });

  type FormState = {
    company_name_ar: string;
    company_name_en: string;
    commercial_reg: string;
    phone: string;
    city: string;
    tax_number: string | undefined;
    district: string | undefined;
    address: string | undefined;
    description_ar: string | undefined;
    description_en: string | undefined;
    logo: string | undefined;
    website: string | undefined;
  };

  const state = reactive<FormState>({
    company_name_ar: props.supplier?.company_name_ar ?? '',
    company_name_en: props.supplier?.company_name_en ?? '',
    commercial_reg: props.supplier?.commercial_reg ?? '',
    phone: props.supplier?.phone ?? '',
    city: props.supplier?.city ?? '',
    tax_number: props.supplier?.tax_number ?? undefined,
    district: props.supplier?.district ?? undefined,
    address: props.supplier?.address ?? undefined,
    description_ar: props.supplier?.description_ar ?? undefined,
    description_en: props.supplier?.description_en ?? undefined,
    logo: props.supplier?.logo ?? undefined,
    website: props.supplier?.website ?? undefined,
  });

  const errors = reactive<Partial<Record<keyof FormState, string>>>({});
  function validate(): boolean {
    const result = schema.safeParse(state);
    Object.keys(errors).forEach((k) => {
      (errors as Record<string, unknown>)[k] = undefined;
    });
    if (!result.success) {
      for (const issue of result.error.issues) {
        const key = issue.path[0] as keyof FormState;
        if (key && !errors[key]) {
          errors[key] = issue.message;
        }
      }
      return false;
    }
    return true;
  }

  function onSubmit() {
    if (!validate()) return;
    emit('submit', state as StoreSupplierPayload | UpdateSupplierPayload);
  }
</script>

<template>
  <UForm :state="state" class="space-y-5" @submit.prevent="onSubmit">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <!-- Arabic company name -->
      <UFormGroup
        :label="t('suppliers.fields.company_name_ar')"
        :error="errors.company_name_ar"
        required
      >
        <UInput
          v-model="state.company_name_ar"
          dir="rtl"
          :placeholder="t('suppliers.placeholders.company_name_ar')"
        />
      </UFormGroup>

      <!-- English company name -->
      <UFormGroup
        :label="t('suppliers.fields.company_name_en')"
        :error="errors.company_name_en"
        required
      >
        <UInput
          v-model="state.company_name_en"
          dir="ltr"
          :placeholder="t('suppliers.placeholders.company_name_en')"
        />
      </UFormGroup>

      <!-- Commercial registration -->
      <UFormGroup
        :label="t('suppliers.fields.commercial_reg')"
        :error="errors.commercial_reg"
        required
      >
        <UInput v-model="state.commercial_reg" dir="ltr" />
      </UFormGroup>

      <!-- Tax number -->
      <UFormGroup :label="t('suppliers.fields.tax_number')" :error="errors.tax_number">
        <UInput v-model="state.tax_number" dir="ltr" />
      </UFormGroup>

      <!-- Phone -->
      <UFormGroup :label="t('suppliers.fields.phone')" :error="errors.phone" required>
        <UInput v-model="state.phone" dir="ltr" placeholder="05XXXXXXXX" />
      </UFormGroup>

      <!-- City -->
      <UFormGroup :label="t('suppliers.fields.city')" :error="errors.city" required>
        <UInput v-model="state.city" />
      </UFormGroup>

      <!-- District -->
      <UFormGroup :label="t('suppliers.fields.district')" :error="errors.district">
        <UInput v-model="state.district" />
      </UFormGroup>

      <!-- Address -->
      <UFormGroup
        :label="t('suppliers.fields.address')"
        :error="errors.address"
        class="sm:col-span-2"
      >
        <UTextarea v-model="state.address" :rows="2" />
      </UFormGroup>

      <!-- Description AR -->
      <UFormGroup :label="t('suppliers.fields.description_ar')" :error="errors.description_ar">
        <UTextarea v-model="state.description_ar" dir="rtl" :rows="3" />
      </UFormGroup>

      <!-- Description EN -->
      <UFormGroup :label="t('suppliers.fields.description_en')" :error="errors.description_en">
        <UTextarea v-model="state.description_en" dir="ltr" :rows="3" />
      </UFormGroup>

      <!-- Logo URL -->
      <UFormGroup :label="t('suppliers.fields.logo')" :error="errors.logo">
        <UInput v-model="state.logo" dir="ltr" placeholder="https://" />
      </UFormGroup>

      <!-- Website -->
      <UFormGroup :label="t('suppliers.fields.website')" :error="errors.website">
        <UInput v-model="state.website" dir="ltr" placeholder="https://" />
      </UFormGroup>
    </div>

    <div class="flex justify-end">
      <UButton type="submit" :loading="loading">
        {{ supplier ? t('common.save_changes') : t('suppliers.create') }}
      </UButton>
    </div>
  </UForm>
</template>
