<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useLocaleRoute } from '../../composables/useLocaleRoute';

const localeRoute = useLocaleRoute();
const { t } = useI18n();

function handleRetry() {
  localeRoute.push('/');
}

function goBack() {
  localeRoute.back();
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
