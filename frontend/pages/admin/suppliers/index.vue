<script setup lang="ts">
  import type { ColumnDef } from '@tanstack/vue-table';
  import type { SupplierProfile, SupplierVerificationStatus } from '~~/types/supplier';

  definePageMeta({ middleware: ['auth'] });

  const { t } = useI18n();
  const supplierStore = useSupplierStore();
  const { notifySuccess } = useNotification();

  const filters = reactive({
    page: 1,
    search: '',
    status: '',
    actionLoading: null as number | null,
  });

  const statusOptions = [
    { label: t('common.all'), value: '' },
    { label: t('suppliers.status.pending'), value: 'pending' },
    { label: t('suppliers.status.verified'), value: 'verified' },
    { label: t('suppliers.status.suspended'), value: 'suspended' },
  ];

  const suppliers = computed<SupplierProfile[]>(() => supplierStore.suppliers as SupplierProfile[]);

  async function load() {
    await supplierStore.fetchList({
      search: filters.search || undefined,
      verification_status: (filters.status as SupplierVerificationStatus) || undefined,
      page: filters.page,
      per_page: 20,
    });
  }

  await load();

  watch(
    () => [filters.search, filters.status],
    () => {
      filters.page = 1;
      load();
    }
  );

  watch(() => filters.page, load);

  async function handleVerify(supplier: SupplierProfile) {
    filters.actionLoading = supplier.id;
    try {
      await supplierStore.verifySupplier(supplier.id);
      notifySuccess(t('suppliers.verified_successfully'));
    } finally {
      filters.actionLoading = null;
    }
  }

  async function handleSuspend(supplier: SupplierProfile) {
    filters.actionLoading = supplier.id;
    try {
      await supplierStore.suspendSupplier(supplier.id);
      notifySuccess(t('suppliers.suspended_successfully'));
    } finally {
      filters.actionLoading = null;
    }
  }

  const columns: ColumnDef<SupplierProfile>[] = [
    { accessorKey: 'company_name_ar', header: t('suppliers.fields.company_name_ar') },
    { accessorKey: 'city', header: t('suppliers.fields.city') },
    { accessorKey: 'phone', header: t('suppliers.fields.phone') },
    { accessorKey: 'verification_status', header: t('suppliers.fields.verification_status') },
    { id: 'actions', header: '' },
  ];

  useHead({ title: t('suppliers.admin_list_title') });
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-[#171717] dark:text-white">
        {{ t('suppliers.admin_list_title') }}
      </h1>
    </div>

    <!-- Filters -->
    <div class="mb-5 flex flex-wrap gap-3">
      <UInput
        v-model="filters.search"
        :placeholder="t('common.search')"
        icon="i-heroicons-magnifying-glass"
        class="w-full sm:w-64"
      />
      <USelect v-model="filters.status" :options="statusOptions" class="w-48" />
    </div>

    <!-- Table -->
    <div
      class="overflow-hidden rounded-xl bg-white dark:bg-[#111111]"
      style="box-shadow: 0px 0px 0px 1px rgba(0, 0, 0, 0.08)"
    >
      <UTable :columns="columns" :data="suppliers" :loading="supplierStore.isLoading">
        <template #verification_status-cell="{ row }">
          <SupplierVerificationStatusBadge :status="row.original.verification_status" />
        </template>

        <template #actions-cell="{ row }">
          <div class="flex items-center gap-2">
            <UButton
              v-if="row.original.verification_status !== 'verified'"
              size="xs"
              color="success"
              variant="ghost"
              :loading="filters.actionLoading === row.original.id"
              @click="handleVerify(row.original)"
            >
              {{ t('suppliers.verify') }}
            </UButton>
            <UButton
              v-if="row.original.verification_status !== 'suspended'"
              size="xs"
              color="error"
              variant="ghost"
              :loading="filters.actionLoading === row.original.id"
              @click="handleSuspend(row.original)"
            >
              {{ t('suppliers.suspend') }}
            </UButton>
          </div>
        </template>
      </UTable>
    </div>

    <!-- Pagination -->
    <div
      v-if="supplierStore.meta && supplierStore.meta.last_page > 1"
      class="mt-6 flex justify-center"
    >
      <UPagination
        v-model="filters.page"
        :total="supplierStore.meta.total"
        :page-count="supplierStore.meta.per_page"
      />
    </div>
  </div>
</template>
