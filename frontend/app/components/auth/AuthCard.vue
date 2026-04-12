<script setup lang="ts">
    defineProps<{
        title: string;
        subtitle?: string;
    }>();
</script>

<template>
    <UCard
        class="w-full max-w-md"
        :ui="{
            root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]',
        }"
    >
        <template #header>
            <div class="flex flex-col items-center gap-2 py-2">
                <img src="/logo.svg" alt="بنيان" class="h-8" />
                <h1
                    class="text-xl font-semibold tracking-[-0.96px] text-[#171717] dark:text-[#ededed]"
                >
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="text-sm text-[#666] dark:text-[#888]">
                    {{ subtitle }}
                </p>
            </div>
        </template>

        <slot />
    </UCard>
</template>
