<script setup lang="ts">
  definePageMeta({
    middleware: ['auth', 'role'],
    requiredRole: 'admin',
  });

  const { t } = useI18n();
  const route = useRoute();
  const toast = useToast();
  const roleId = route.params.id as string;

  interface Permission {
    id: number;
    name: string;
    display_name: string;
    group: string;
    description: string;
  }

  interface RoleDetail {
    id: number;
    name: string;
    display_name: string;
    display_name_ar: string;
    description: string;
    permissions_count: number;
    permissions: Permission[];
  }

  const { data: roleData, status } = await useFetch<{ success: boolean; data: RoleDetail }>(
    `/api/v1/admin/roles/${roleId}`,
    {
      headers: useRequestHeaders(['cookie']),
    }
  );

  const role = computed(() => roleData.value?.data);

  // Track selected permission IDs
  const selectedPermissionIds = ref<Set<number>>(new Set());

  // Initialize from loaded role permissions
  watch(
    () => role.value?.permissions,
    (permissions) => {
      if (permissions) {
        selectedPermissionIds.value = new Set(permissions.map((p) => p.id));
      }
    },
    { immediate: true }
  );

  // Fetch all available permissions
  const { data: allPermsData } = await useFetch<{ success: boolean; data: Permission[] }>(
    '/api/v1/admin/permissions',
    {
      headers: useRequestHeaders(['cookie']),
    }
  );

  const allPermissions = computed(() => allPermsData.value?.data ?? []);

  // Group permissions by group field
  const groupedPermissions = computed(() => {
    const groups: Record<string, Permission[]> = {};
    for (const perm of allPermissions.value) {
      const group = perm.group ?? 'other';
      if (!groups[group]) {
        groups[group] = [];
      }
      groups[group].push(perm);
    }
    return groups;
  });

  function isPermissionSelected(permId: number): boolean {
    return selectedPermissionIds.value?.has(permId) ?? false;
  }

  function togglePermission(permId: number) {
    const currentSet = selectedPermissionIds.value ?? new Set<number>();
    const newSet = new Set(currentSet);
    if (newSet.has(permId)) {
      newSet.delete(permId);
    } else {
      newSet.add(permId);
    }
    selectedPermissionIds.value = newSet;
  }

  const isSaving = ref(false);

  async function savePermissions() {
    isSaving.value = true;
    try {
      const permIds = Array.from(selectedPermissionIds.value ?? []);
      await $fetch(`/api/v1/admin/roles/${roleId}/permissions`, {
        method: 'PUT',
        body: {
          permission_ids: permIds,
        },
      });
      toast.add({
        title: t('rbac.permissions_saved'),
        color: 'success',
      });
    } catch {
      toast.add({
        title: t('rbac.permissions_save_error'),
        color: 'error',
      });
    } finally {
      isSaving.value = false;
    }
  }
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-[#171717] dark:text-white tracking-[-0.02em]">
          {{ role?.display_name ?? '' }}
        </h1>
        <p class="text-sm text-[#666666] mt-1">{{ role?.description ?? '' }}</p>
      </div>
      <UButton
        :loading="!!isSaving.value"
        color="primary"
        icon="i-heroicons-check"
        :label="t('save')"
        @click="savePermissions"
      />
    </div>

    <!-- Loading -->
    <div v-if="status === 'pending'" class="flex justify-center py-12">
      <UIcon name="i-heroicons-arrow-path" class="animate-spin text-2xl text-[#666666]" />
    </div>

    <!-- Permission Groups -->
    <div v-else class="space-y-4">
      <UCard v-for="(permissions, group) in groupedPermissions" :key="group">
        <template #header>
          <h3 class="text-sm font-semibold text-[#171717] dark:text-white capitalize">
            {{ t(`rbac.groups.${group}`) }}
          </h3>
        </template>

        <div class="space-y-3">
          <div
            v-for="perm in permissions"
            :key="perm.id"
            class="flex items-center justify-between py-1"
          >
            <div>
              <p class="text-sm font-medium text-[#171717] dark:text-white">
                {{ perm.display_name }}
              </p>
              <p class="text-xs text-[#666666]">{{ perm.name }}</p>
            </div>
            <UToggle
              :model-value="isPermissionSelected(perm.id)"
              @update:model-value="togglePermission(perm.id)"
            />
          </div>
        </div>
      </UCard>
    </div>
  </div>
</template>
