<script setup lang="ts">
  definePageMeta({
    middleware: ['auth', 'role'],
    requiredRole: 'admin',
  });

  const { t } = useI18n();
  const localePath = useLocalePath();

  interface RoleRow {
    id: number;
    name: string;
    display_name: string;
    display_name_ar: string;
    description: string;
    permissions_count: number;
  }

  const { data: roles, status } = await useFetch<{ success: boolean; data: RoleRow[] }>(
    '/api/v1/admin/roles',
    {
      headers: useRequestHeaders(['cookie']),
    }
  );

  const columns = [
    { key: 'name', label: t('rbac.role_name') },
    { key: 'display_name', label: t('rbac.display_name') },
    { key: 'display_name_ar', label: t('rbac.display_name_ar') },
    { key: 'permissions_count', label: t('rbac.permissions_count') },
    { key: 'actions', label: t('rbac.actions') },
  ];

  const rows = computed<RoleRow[]>(() => roles.value?.data ?? []);
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold text-[#171717] dark:text-white tracking-[-0.02em]">
        {{ t('rbac.roles_management') }}
      </h1>
    </div>

    <UCard>
      <UTable :rows="rows" :columns="columns as any" :loading="status === 'pending'">
        <template #name-data="{ row }">
          <UBadge color="neutral" variant="subtle" size="sm">
            {{ (row as unknown as RoleRow).name }}
          </UBadge>
        </template>

        <template #permissions_count-data="{ row }">
          <span class="text-sm text-[#666666]">{{
            (row as unknown as RoleRow).permissions_count
          }}</span>
        </template>

        <template #actions-data="{ row }">
          <UButton
            :to="localePath(`/admin/roles/${(row as unknown as RoleRow).id}`)"
            color="neutral"
            variant="ghost"
            size="sm"
            icon="i-heroicons-eye"
            :label="t('rbac.view_details')"
          />
        </template>
      </UTable>
    </UCard>
  </div>
</template>
