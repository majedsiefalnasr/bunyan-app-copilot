<script setup lang="ts">
import { computed } from 'vue';
import type { PropType } from 'vue';
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
  <div class="min-h-screen flex items-center justify-center bg-white px-4">
    <div class="max-w-md w-full">
      <!-- Icon -->
      <div class="flex justify-center mb-6">
        <div
          v-if="statusCode === 500"
          class="w-16 h-16 rounded-lg bg-red-50 flex items-center justify-center"
        >
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4m0 4v2m0 0v2m0-2v2m-6-7h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2z"
            />
          </svg>
        </div>
        <div v-else class="text-6xl font-bold text-gray-200">{{ statusCode }}</div>
      </div>

      <!-- Heading -->
      <h1 class="text-2xl font-semibold text-center mb-2 text-[#171717] tracking-tight">
        {{ content?.title }}
      </h1>

      <!-- Description -->
      <p class="text-sm text-[#666] text-center mb-8">
        {{ content?.message }}
      </p>

      <!-- Buttons -->
      <div class="flex gap-3 justify-center">
        <button
          class="px-4 py-2 bg-[#171717] text-white rounded-[6px] font-medium hover:bg-[#2d2d2d] transition-colors"
          @click="goHome"
        >
          Go to Home
        </button>
        <button
          class="px-4 py-2 border border-[#171717] text-[#171717] rounded-[6px] font-medium hover:bg-gray-50 transition-colors"
          @click="goBack"
        >
          Go Back
        </button>
      </div>
    </div>
  </div>
</template>
