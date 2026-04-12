<script setup lang="ts">
    definePageMeta({
        middleware: ['auth'],
    });

    const { t } = useI18n();
    const { user, logout } = useAuth();
</script>

<template>
    <div class="min-h-screen bg-[#fafafa] dark:bg-[#0a0a0a] p-8">
        <div class="mx-auto max-w-2xl">
            <div
                class="rounded-lg bg-white dark:bg-[#111] p-8 shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]"
            >
                <h1
                    class="text-2xl font-semibold tracking-[-1.28px] text-[#171717] dark:text-[#ededed] mb-4"
                >
                    {{ t('auth.dashboard.title') }}
                </h1>

                <p class="text-[#666] dark:text-[#888] mb-6">
                    {{ t('auth.dashboard.welcome', { name: user?.name }) }}
                </p>

                <UButton variant="outline" @click="logout">
                    {{ t('auth.logout.button') }}
                </UButton>
            </div>
        </div>
    </div>
</template>
