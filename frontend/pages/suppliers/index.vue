<script setup lang="ts">
  import { ref, watch } from 'vue';

  definePageMeta({ auth: false });

  const { t } = useI18n();
  const supplierStore = useSupplierStore();

  const search = ref('');
  const city = ref('');
  const page = ref(1);

  async function load() {
    await supplierStore.fetchList({
      search: search.value || undefined,
      city: city.value || undefined,
      page: page.value,
      per_page: 12,
    });
  }

  await load();

  watch([search, city], () => {
    page.value = 1;
    load();
  });

  watch(page, load);

  useHead({
    title: t('suppliers.list_title'),
  });
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-semibold tracking-tight text-[#171717] dark:text-white">
        {{ t('suppliers.list_title') }}
      </h1>
      <p class="mt-1 text-sm text-[#666666]">{{ t('suppliers.list_subtitle') }}</p>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-wrap gap-3">
      <UInput
        v-model="search"
        :placeholder="t('common.search')"
        icon="i-heroicons-magnifying-glass"
        class="w-full sm:w-64"
      />
      <UInput v-model="city" :placeholder="t('suppliers.fields.city')" class="w-full sm:w-48" />
    </div>

    <!-- Loading skeleton -->
    <div
      v-if="supplierStore.isLoading"
      class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
    >
      <USkeleton v-for="n in 6" :key="n" class="h-44 rounded-xl" />
    </div>

    <!-- Results -->
    <template v-else>
      <div
        v-if="supplierStore.suppliers.length"
        class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
      >
        <SupplierCard
          v-for="supplier in supplierStore.suppliers"
          :key="supplier.id"
          :supplier="supplier"
        />
      </div>

      <div v-else class="py-16 text-center text-[#999]">
        {{ t('suppliers.no_results') }}
      </div>

      <!-- Pagination -->
      <div
        v-if="supplierStore.meta && supplierStore.meta.last_page > 1"
        class="mt-8 flex justify-center"
      >
        <UPagination
          v-model="page"
          :total="supplierStore.meta.total"
          :page-count="supplierStore.meta.per_page"
        />
      </div>
    </template>
  </div>
</template>
