<script setup lang="ts">
    import { computed } from 'vue';

    const { t, locale } = useI18n();

    const loginTo = computed(() => `/${locale.value}/auth/login`);
    const registerTo = computed(() => `/${locale.value}/auth/register`);
</script>

<template>
    <div class="min-h-screen flex flex-col bg-white dark:bg-[#0a0a0a]">
        <!-- Marketing header: bg-white/80 backdrop-blur per DESIGN.md -->
        <header
            class="sticky top-0 z-40 bg-white/80 dark:bg-[#0a0a0a]/80 backdrop-blur shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08)] flex items-center h-14 px-6"
        >
            <!-- Logo -->
            <NuxtLink to="/" class="flex items-center gap-2 me-auto">
                <img src="/logo.svg" alt="بنيان" class="h-7" />
            </NuxtLink>

            <!-- Auth actions -->
            <div class="flex items-center gap-2">
                <UButton variant="ghost" color="neutral" :to="loginTo">
                    {{ t('auth.sign_in') }}
                </UButton>
                <UButton color="primary" :to="registerTo">
                    {{ t('auth.register') }}
                </UButton>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <AppFooter />
    </div>
</template>
