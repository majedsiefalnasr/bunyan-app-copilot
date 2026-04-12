<script setup lang="ts">
    const { t } = useI18n();
    const version = '1.0.0';
</script>

<template>
    <footer
        class="shadow-[0_-1px_0_0_rgba(0,0,0,0.08)] py-4 px-6 text-sm text-[#666666] dark:text-[#888888] flex items-center justify-between"
    >
        <span>{{ t('layout.footer_copyright') }}</span>
        <span class="text-xs">v{{ version }}</span>
    </footer>
</template>
