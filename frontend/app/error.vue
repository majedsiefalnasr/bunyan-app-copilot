<script setup lang="ts">
  import type { PropType } from 'vue';
  import { computed } from 'vue';
  import { useI18n } from 'vue-i18n';
  import { useRouter } from 'vue-router';

  const props = defineProps({
    error: {
      type: Object as PropType<{ statusCode?: number } | null>,
      default: () => null,
    },
  } as const);

  const { t, locale } = useI18n();
  const router = useRouter();

  // Get status code from error object
  const statusCode = computed(() => props.error?.statusCode || 500);

  function goHome() {
    router.push(`/${locale.value}`);
  }

  function goBack() {
    router.back();
  }

  // Map status codes to titles and messages
  const errorContent: Record<number, { title: string; message: string; icon: string }> = {
    404: {
      title: t('errors.not_found_title', 'Page Not Found'),
      message: t(
        'errors.not_found_message',
        'The resource you are looking for does not exist or has been removed.'
      ),
      icon: '404',
    },
    403: {
      title: t('errors.access_denied_title', 'Access Denied'),
      message: t(
        'errors.access_denied_message',
        'You do not have permission to access this resource.'
      ),
      icon: '403',
    },
    500: {
      title: t('errors.server_error_title', 'Something Went Wrong'),
      message: t(
        'errors.server_error_message',
        'We encountered an unexpected error. Please try again later.'
      ),
      icon: '500',
    },
  };

  const content = computed(
    () => errorContent[statusCode.value as keyof typeof errorContent] || errorContent[500]
  );
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-6 bg-[#fafafa] dark:bg-[#0a0a0a]">
    <UCard
      class="w-full max-w-md"
      :ui="{
        root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)] dark:shadow-[0px_0px_0px_1px_rgba(255,255,255,0.08)]',
      }"
    >
      <template #header>
        <div class="flex justify-center py-2">
          <span class="text-5xl font-semibold text-[#e5e5e5] dark:text-[#333] tracking-tight">
            {{ statusCode }}
          </span>
        </div>
      </template>

      <UAlert
        :color="statusCode === 404 ? 'warning' : 'error'"
        :title="content?.title ?? ''"
        :description="content?.message ?? ''"
        :icon="
          statusCode === 404
            ? 'i-heroicons-magnifying-glass'
            : statusCode === 403
              ? 'i-heroicons-shield-exclamation'
              : 'i-heroicons-exclamation-triangle'
        "
        variant="soft"
      />

      <template #footer>
        <div class="flex gap-3 justify-center">
          <UButton color="neutral" variant="outline" icon="i-heroicons-arrow-left" @click="goBack">
            {{ t('errors.go_back') }}
          </UButton>
          <UButton color="neutral" variant="solid" icon="i-heroicons-home" @click="goHome">
            {{ t('errors.go_home') }}
          </UButton>
        </div>
      </template>
    </UCard>
  </div>
</template>
