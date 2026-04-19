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

  const config = badgeConfig[props.status];
</script>

<template>
  <UBadge :label="config.label" :color="config.color" variant="subtle" size="xs" />
</template>
