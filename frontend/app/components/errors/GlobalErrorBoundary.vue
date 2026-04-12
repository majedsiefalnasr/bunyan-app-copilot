<script setup lang="ts">
import { onErrorCaptured, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import { useToast } from '../../../composables/useToast';

interface CaughtError {
  message: string;
  stack?: string;
  timestamp: number;
}

const router = useRouter();
const { t } = useI18n();
const toast = useToast();

const hasError = ref(false);
const error = ref<CaughtError | null>(null);

onErrorCaptured((err, instance, info) => {
  // Set error state
  hasError.value = true;
  error.value = {
    message: err instanceof Error ? err.message : String(err),
    stack: err instanceof Error ? err.stack : undefined,
    timestamp: Date.now(),
  };

  // Log error with context
  console.error('[GlobalErrorBoundary]', {
    message: error.value.message,
    info,
    stack: error.value.stack,
    component: instance?.$options.name || 'Unknown',
  });

  // Show toast notification
  toast.showError(t('errors.component_error', 'An unexpected error occurred'));

  // Return false to prevent error propagation
  return false;
});

function handleReload() {
  hasError.value = false;
  error.value = null;
  window.location.reload();
}

function handleBack() {
  hasError.value = false;
  error.value = null;
  router.back();
}
</script>

<template>
  <div v-if="hasError" class="flex items-center justify-center min-h-screen bg-white">
    <div
      class="max-w-md w-full mx-4 p-6 rounded-lg shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]"
    >
      <!-- Error Icon -->
      <div class="flex justify-center mb-4">
        <div class="w-12 h-12 rounded-lg bg-red-50 flex items-center justify-center">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4v2m0 0v2m0-2v2m-6-7h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2z"
            />
          </svg>
        </div>
      </div>

      <!-- Error Message -->
      <h2 class="text-lg font-semibold text-center mb-2 text-[#171717] tracking-tight">
        {{ t('errors.something_went_wrong', 'Something Went Wrong') }}
      </h2>

      <p class="text-sm text-[#666] text-center mb-6">
        {{
          t(
            'errors.error_boundary_message',
            'We encountered an unexpected error. Please reload the page or go back.'
          )
        }}
      </p>

      <!-- Error Details (dev mode only) -->
      <details v-if="error" class="mb-6 text-xs text-[#999]">
        <summary class="cursor-pointer font-medium mb-2">
          {{ t('errors.details', 'Details') }}
        </summary>
        <pre class="bg-gray-50 p-2 rounded text-[#666] overflow-auto max-h-32 mt-2">{{
          error.message
        }}</pre>
      </details>

      <!-- Buttons -->
      <div class="flex gap-3">
        <UButton
          :label="t('errors.reload', 'Reload')"
          class="flex-1 rounded-[6px]"
          color="neutral"
          @click="handleReload"
        />
        <UButton
          :label="t('errors.go_back', 'Go Back')"
          class="flex-1 rounded-[6px]"
          variant="outline"
          @click="handleBack"
        />
      </div>
    </div>
  </div>

  <slot v-else />
</template>
