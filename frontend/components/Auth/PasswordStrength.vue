<template>
  <div class="flex flex-col gap-1">
    <div class="h-1 bg-gradient-to-r from-red-400 via-yellow-400 to-green-400 rounded-full" />
    <div class="flex items-center justify-between px-3 py-2 bg-[#fafafa] dark:bg-[#1a1a1a] rounded">
      <span class="text-xs font-medium text-[#666666] dark:text-[#999999]">
        {{ strengthLabel }}
      </span>
      <span class="text-xs text-[#999999] dark:text-[#666666]"> {{ displayScore }}/100 </span>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { useDebounce } from '@vueuse/core';
  import { computed } from 'vue';

  interface Props {
    score: number;
  }

  const props = defineProps<Props>();

  // Debounced score to prevent CPU storm during fast typing
  const debouncedScore = useDebounce(() => props.score, 300);

  const strengthLabel = computed(() => {
    const score = debouncedScore.value;
    if (score < 25) return 'ضعيفة / Weak';
    if (score < 50) return 'متوسطة / Fair';
    if (score < 75) return 'جيدة / Good';
    return 'قوية / Strong';
  });

  const displayScore = computed(() => debouncedScore.value);
</script>
