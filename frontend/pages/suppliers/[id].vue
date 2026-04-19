<script setup lang="ts">
  definePageMeta({ auth: false });

  const { t } = useI18n();
  const route = useRoute();
  const supplierStore = useSupplierStore();

  const id = Number(route.params.id);

  await supplierStore.fetchOne(id);

  const supplier = computed(() => supplierStore.currentSupplier);

  useHead(() => ({
    title: supplier.value?.company_name_ar ?? t('suppliers.profile_title'),
  }));
</script>

<template>
  <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <template v-if="supplier">
      <!-- Header card -->
      <div
        class="mb-6 rounded-xl bg-white dark:bg-[#111111] p-6"
        style="box-shadow: 0px 0px 0px 1px rgba(0, 0, 0, 0.08)"
      >
        <div class="flex items-start gap-5">
          <!-- Logo -->
          <div class="h-20 w-20 shrink-0 overflow-hidden rounded-xl bg-[#f5f5f5] dark:bg-[#1a1a1a]">
            <img
              v-if="supplier.logo"
              :src="supplier.logo"
              :alt="supplier.company_name_ar"
              class="h-full w-full object-cover"
            />
            <div
              v-else
              class="flex h-full w-full items-center justify-center text-2xl font-semibold text-[#999]"
            >
              {{ supplier.company_name_ar.charAt(0) }}
            </div>
          </div>

          <!-- Info -->
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="text-xl font-semibold text-[#171717] dark:text-white">
                {{ supplier.company_name_ar }}
              </h1>
              <SupplierVerificationStatusBadge :status="supplier.verification_status" />
            </div>
            <p class="mt-0.5 text-sm text-[#666666]">{{ supplier.company_name_en }}</p>
            <p class="mt-1 text-xs text-[#999]">
              {{ supplier.city
              }}<template v-if="supplier.district"> · {{ supplier.district }}</template>
            </p>

            <!-- Rating -->
            <div
              v-if="supplier.total_ratings > 0"
              class="mt-2 flex items-center gap-1 text-sm text-[#666666]"
            >
              <UIcon name="i-heroicons-star-solid" class="h-4 w-4 text-amber-400" />
              <span class="font-medium">{{ supplier.rating_avg }}</span>
              <span class="text-[#999]">({{ supplier.total_ratings }})</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Details card -->
      <div
        class="rounded-xl bg-white dark:bg-[#111111] p-6 space-y-4"
        style="box-shadow: 0px 0px 0px 1px rgba(0, 0, 0, 0.08)"
      >
        <div v-if="supplier.description_ar" dir="rtl">
          <h2 class="mb-1 text-sm font-medium text-[#171717] dark:text-white">
            {{ t('suppliers.fields.description_ar') }}
          </h2>
          <p class="text-sm text-[#666666]">{{ supplier.description_ar }}</p>
        </div>

        <div v-if="supplier.description_en" dir="ltr">
          <h2 class="mb-1 text-sm font-medium text-[#171717] dark:text-white">
            {{ t('suppliers.fields.description_en') }}
          </h2>
          <p class="text-sm text-[#666666]">{{ supplier.description_en }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-[#999]">{{ t('suppliers.fields.phone') }}</span>
            <p class="mt-0.5 font-medium" dir="ltr">{{ supplier.phone }}</p>
          </div>
          <div v-if="supplier.website">
            <span class="text-[#999]">{{ t('suppliers.fields.website') }}</span>
            <a
              :href="supplier.website"
              target="_blank"
              rel="noopener noreferrer"
              class="mt-0.5 block font-medium text-blue-600 hover:underline"
              dir="ltr"
              >{{ supplier.website }}</a
            >
          </div>
        </div>
      </div>
    </template>

    <!-- 404 state -->
    <div v-else class="py-16 text-center text-[#999]">
      {{ t('suppliers.not_found') }}
    </div>
  </div>
</template>
