<template>
  <UCard
    :ui="{
      root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]',
      body: 'p-6',
      header: { base: 'px-6 py-4 border-b border-[rgba(0,0,0,0.08)]' },
    }"
    class="rounded-lg w-full"
  >
    <template v-if="title || subtitle" #header>
      <div class="flex flex-col gap-1">
        <h2 v-if="title" class="text-[#171717] font-semibold text-lg tracking-tight">
          {{ title }}
        </h2>
        <p v-if="subtitle" class="text-[#999999] text-sm font-normal">
          {{ subtitle }}
        </p>
      </div>
    </template>

    <slot />
  </UCard>
</template>

<script setup lang="ts">
  interface Props {
    title?: string;
    subtitle?: string;
  }

  defineProps<Props>();
</script>
