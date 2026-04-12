<script setup lang="ts">
    import { computed } from 'vue';
    import { useAuthStore } from '../../../stores/auth';
    import type { NavItem } from '../../../types/index';
    import { UserRole } from '../../../types/index';

    const props = defineProps<{
        items: NavItem[];
    }>();

    const authStore = useAuthStore();
    const { t } = useI18n();

    // Map NavItem[] to Nuxt UI UNavigationMenu format with translated labels
    const navigationItems = computed(() =>
        props.items.map((item) => ({
            label: t(item.labelKey),
            icon: item.icon,
            to: item.to,
            badge: item.badge,
        }))
    );

    const isAdmin = computed(() => authStore.role === UserRole.Admin);
</script>

<template>
    <aside
        class="hidden md:flex flex-col w-64 shrink-0 bg-white dark:bg-[#0a0a0a] shadow-[1px_0_0_0_rgba(0,0,0,0.08)]"
    >
        <!-- Sidebar header: logo -->
        <div class="h-14 flex items-center px-4 shadow-[0_1px_0_0_rgba(0,0,0,0.08)]">
            <NuxtLink to="/" class="flex items-center gap-2">
                <img src="/logo.svg" alt="بنيان" class="h-7" />
            </NuxtLink>
        </div>

        <!-- Navigation items -->
        <div class="flex-1 overflow-y-auto py-3">
            <UNavigationMenu orientation="vertical" :items="navigationItems" class="px-2" />
        </div>

        <!-- Sidebar footer: user info -->
        <div class="shadow-[0_-1px_0_0_rgba(0,0,0,0.08)] p-3 flex items-center gap-2">
            <UAvatar :alt="authStore.user?.name ?? 'User'" size="sm" />
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-[#171717] dark:text-white truncate">
                    {{ authStore.user?.name ?? '' }}
                </p>
                <p class="text-xs text-[#666666] truncate">
                    {{ authStore.user?.email ?? '' }}
                </p>
            </div>
            <UBadge v-if="isAdmin" color="neutral" variant="subtle" size="sm" class="shrink-0">
                {{ t('roles.admin') }}
            </UBadge>
        </div>
    </aside>
</template>
