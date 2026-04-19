<script setup lang="ts">
  definePageMeta({ middleware: ['auth'] });

  const { t } = useI18n();
  const supplierStore = useSupplierStore();
  const authStore = useAuthStore();
  const { notifySuccess } = useNotification();

  const isEditing = ref(false);
  const isSubmitting = ref(false);

  const userId = computed(() => authStore.user?.id);

  // Try to load own supplier profile
  onMounted(async () => {
    if (userId.value) {
      try {
        await supplierStore.fetchList({ per_page: 1, page: 1 });
        // Check if the current contractor already has a profile by fetching the first
        // result that belongs to them — or we'll just show the create form
      } catch {
        // No profile yet
      }
    }
  });

  const supplier = computed(() => supplierStore.currentSupplier);

  async function handleSubmit(payload: Record<string, unknown>) {
    isSubmitting.value = true;
    try {
      if (supplier.value) {
        await supplierStore.updateSupplier(supplier.value.id, payload);
        notifySuccess(t('suppliers.updated_successfully'));
      } else {
        await supplierStore.createSupplier(payload as never);
        notifySuccess(t('suppliers.created_successfully'));
      }
      isEditing.value = false;
    } finally {
      isSubmitting.value = false;
    }
  }

  useHead({ title: t('suppliers.my_profile') });
</script>

<template>
  <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-xl font-semibold text-[#171717] dark:text-white">
        {{ t('suppliers.my_profile') }}
      </h1>
      <UButton
        v-if="supplier && !isEditing"
        variant="outline"
        size="sm"
        @click="
          () => {
            isEditing.value = true;
          }
        "
      >
        {{ t('common.edit') }}
      </UButton>
    </div>

    <!-- Profile view -->
    <template v-if="supplier && !isEditing">
      <div
        class="rounded-xl bg-white dark:bg-[#111111] p-6"
        style="box-shadow: 0px 0px 0px 1px rgba(0, 0, 0, 0.08)"
      >
        <div class="flex items-center gap-3">
          <h2 class="text-base font-medium text-[#171717] dark:text-white">
            {{ supplier.company_name_ar }}
          </h2>
          <SupplierVerificationStatusBadge :status="supplier.verification_status" />
        </div>
        <p class="mt-0.5 text-sm text-[#666666]">{{ supplier.company_name_en }}</p>
        <p class="mt-2 text-sm text-[#999]">{{ supplier.city }}</p>
      </div>
    </template>

    <!-- Edit / create form -->
    <template v-else>
      <div
        class="rounded-xl bg-white dark:bg-[#111111] p-6"
        style="box-shadow: 0px 0px 0px 1px rgba(0, 0, 0, 0.08)"
      >
        <SupplierForm
          :supplier="supplier ?? undefined"
          :loading="isSubmitting"
          @submit="handleSubmit"
        />
        <div v-if="isEditing" class="mt-3">
          <UButton
            variant="ghost"
            size="sm"
            @click="
              () => {
                isEditing.value = false;
              }
            "
          >
            {{ t('common.cancel') }}
          </UButton>
        </div>
      </div>
    </template>
  </div>
</template>
