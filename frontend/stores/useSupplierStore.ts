import { defineStore } from 'pinia';
import { ref } from 'vue';
import type {
  PaginatedSuppliers,
  StoreSupplierPayload,
  SupplierListFilters,
  SupplierProfile,
  UpdateSupplierPayload,
} from '~~/types/supplier';

export const useSupplierStore = defineStore('supplier', () => {
  const suppliers = ref<SupplierProfile[]>([]);
  const currentSupplier = ref<SupplierProfile | null>(null);
  const isLoading = ref(false);
  const meta = ref<PaginatedSuppliers['meta'] | null>(null);

  const composable = useSupplier();

  async function fetchList(filters: SupplierListFilters = {}) {
    isLoading.value = true;
    try {
      const result = await composable.list(filters);
      suppliers.value = result.data;
      meta.value = result.meta;
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchOne(id: number) {
    isLoading.value = true;
    try {
      const result = await composable.show(id);
      currentSupplier.value = result;
      return result;
    } finally {
      isLoading.value = false;
    }
  }

  async function createSupplier(payload: StoreSupplierPayload): Promise<SupplierProfile> {
    isLoading.value = true;
    try {
      const result = await composable.store(payload);
      suppliers.value.unshift(result);
      return result;
    } finally {
      isLoading.value = false;
    }
  }

  async function updateSupplier(
    id: number,
    payload: UpdateSupplierPayload
  ): Promise<SupplierProfile> {
    isLoading.value = true;
    try {
      const result = await composable.update(id, payload);
      const idx = suppliers.value.findIndex((s) => s.id === id);
      if (idx !== -1) suppliers.value[idx] = result;
      if (currentSupplier.value?.id === id) currentSupplier.value = result;
      return result;
    } finally {
      isLoading.value = false;
    }
  }

  async function verifySupplier(id: number): Promise<SupplierProfile> {
    isLoading.value = true;
    try {
      const result = await composable.verify(id);
      const idx = suppliers.value.findIndex((s) => s.id === id);
      if (idx !== -1) suppliers.value[idx] = result;
      if (currentSupplier.value?.id === id) currentSupplier.value = result;
      return result;
    } finally {
      isLoading.value = false;
    }
  }

  async function suspendSupplier(id: number): Promise<SupplierProfile> {
    isLoading.value = true;
    try {
      const result = await composable.suspend(id);
      const idx = suppliers.value.findIndex((s) => s.id === id);
      if (idx !== -1) suppliers.value[idx] = result;
      if (currentSupplier.value?.id === id) currentSupplier.value = result;
      return result;
    } finally {
      isLoading.value = false;
    }
  }

  function reset() {
    suppliers.value = [];
    currentSupplier.value = null;
    meta.value = null;
    isLoading.value = false;
  }

  return {
    suppliers,
    currentSupplier,
    isLoading,
    meta,
    fetchList,
    fetchOne,
    createSupplier,
    updateSupplier,
    verifySupplier,
    suspendSupplier,
    reset,
  };
});
