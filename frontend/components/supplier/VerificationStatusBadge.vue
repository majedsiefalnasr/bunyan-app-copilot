<script setup lang="ts">
  import type { SupplierVerificationStatus } from '~/types/supplier';

  const props = defineProps<{
    status: SupplierVerificationStatus;
  }>();

  const { t } = useI18n();

  const badgeConfig: Record<
    SupplierVerificationStatus,
    { label: string; color: 'success' | 'warning' | 'error' }
  > = {
    verified: { label: t('suppliers.status.verified'), color: 'success' },
    pending: { label: t('suppliers.status.pending'), color: 'warning' },
    suspended: { label: t('suppliers.status.suspended'), color: 'error' },
  };

  const label = computed(() => badgeConfig[props.status]?.label ?? badgeConfig.pending.label);
  const color = computed(() => badgeConfig[props.status]?.color ?? badgeConfig.pending.color);
</script>

<template>
  <UBadge :label="label" :color="color" variant="subtle" size="xs" />
</template>
