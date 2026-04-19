<script setup lang="ts">
  import type { SupplierProfile } from '~/types/supplier';

  const { supplier } = defineProps<{
    supplier: SupplierProfile;
  }>();

  const { t } = useI18n();
  const localePath = useLocalePath();
</script>

<template>
  <div
    class="rounded-xl bg-white dark:bg-[#111111] p-5 transition-shadow hover:shadow-md"
    style="box-shadow: 0px 0px 0px 1px rgba(0, 0, 0, 0.08)"
  >
    <div class="flex items-start gap-4">
      <!-- Logo -->
      <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-[#f5f5f5] dark:bg-[#1a1a1a]">
        <img
          v-if="supplier.logo"
          :src="supplier.logo"
          :alt="supplier.company_name_ar"
          class="h-full w-full object-cover"
        />
        <div
          v-else
          class="flex h-full w-full items-center justify-center text-xl font-semibold text-[#999]"
        >
          {{ supplier.company_name_ar.charAt(0) }}
        </div>
      </div>

      <!-- Info -->
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <h3 class="truncate text-base font-semibold text-[#171717] dark:text-white">
            {{ supplier.company_name_ar }}
          </h3>
          <SupplierVerificationStatusBadge :status="supplier.verification_status" />
        </div>
        <p class="mt-0.5 truncate text-sm text-[#666666] dark:text-[#888]">
          {{ supplier.company_name_en }}
        </p>
        <p class="mt-1 text-xs text-[#999]">
          {{ supplier.city }}
          <template v-if="supplier.district"> · {{ supplier.district }}</template>
        </p>
      </div>
    </div>

    <!-- Rating -->
    <div
      v-if="supplier.total_ratings > 0"
      class="mt-4 flex items-center gap-1 text-sm text-[#666666]"
    >
      <UIcon name="i-heroicons-star-solid" class="h-4 w-4 text-amber-400" />
      <span class="font-medium">{{ supplier.rating_avg }}</span>
      <span class="text-[#999]">({{ supplier.total_ratings }})</span>
    </div>

    <!-- Actions -->
    <div class="mt-4">
      <UButton
        :to="localePath(`/suppliers/${supplier.id}`)"
        variant="outline"
        size="sm"
        class="w-full"
      >
        {{ t('suppliers.view_profile') }}
      </UButton>
    </div>
  </div>
</template>
