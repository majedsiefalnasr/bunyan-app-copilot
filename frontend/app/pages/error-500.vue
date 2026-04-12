<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useErrorStore } from '~/stores/errorStore';

const router = useRouter();
const { t } = useI18n();
const errorStore = useErrorStore();

const correlationId = computed(() => errorStore.currentError?.correlationId);

function handleRetry() {
  router.push('/');
}

function goBack() {
  router.back();
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-white px-4">
    <div class="max-w-md w-full">
      <!-- 500 Icon -->
      <div class="flex justify-center mb-6">
        <div class="w-16 h-16 rounded-lg bg-red-50 flex items-center justify-center">
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4v2m0 0v2m0-2v2m-6-7h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2z"
            />
          </svg>
        </div>
      </div>

      <!-- Heading -->
      <h1 class="text-2xl font-semibold text-center mb-2 text-[#171717] tracking-tight">
        {{ t('errors.server_error_title', 'Something Went Wrong') }}
      </h1>

      <!-- Description -->
      <p class="text-sm text-[#666] text-center mb-8">
        {{
          t(
            'errors.server_error_message',
            'We encountered an unexpected error. Please try again later.'
          )
        }}
      </p>

      <!-- Correlation ID (Support Reference) -->
      <div v-if="correlationId" class="mb-6 p-3 bg-gray-50 rounded border border-gray-200">
        <p class="text-xs text-[#999] mb-1">
          {{ t('errors.support_reference', 'Support Reference') }}
        </p>
        <p class="text-xs font-mono text-[#666] break-all">{{ correlationId }}</p>
      </div>

      <!-- Buttons -->
      <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
        <UButton
          :label="t('errors.retry', 'Retry')"
          class="rounded-[6px]"
          color="neutral"
          @click="handleRetry"
        />
        <UButton
          :label="t('errors.go_back', 'Go Back')"
          class="rounded-[6px]"
          variant="outline"
          @click="goBack"
        />
      </div>
    </div>
  </div>
</template>
