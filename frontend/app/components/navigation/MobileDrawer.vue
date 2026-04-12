<script setup lang="ts">
    import { computed } from 'vue';
    import { useUiStore } from '../../../stores/ui';
    import { useDirection } from '../../../composables/useDirection';
    import type { NavItem } from '../../../types/index';

    const props = defineProps<{
        items: NavItem[];
    }>();

    const uiStore = useUiStore();
    const { direction } = useDirection();
    const { t } = useI18n();

    const isDrawerOpen = computed({
        get: () => uiStore.isDrawerOpen,
        set: (val: boolean) => {
            if (!val) uiStore.closeDrawer();
            else uiStore.openDrawer();
        },
    });

    // Map to UNavigationMenu format with translated labels
    const navigationItems = computed(() =>
        props.items.map((item) => ({
            label: t(item.labelKey),
            icon: item.icon,
            to: item.to,
            badge: item.badge,
        }))
    );

    // CRITICAL: UDrawer uses `direction` prop ('left'|'right'|'top'|'bottom').
    // The `side` prop does NOT exist in Nuxt UI v4.6.1.
    // RTL logical start = right; LTR logical start = left.
    const drawerDirection = computed(() => (direction.value === 'rtl' ? 'right' : 'left'));
</script>

<template>
    <UDrawer v-model:open="isDrawerOpen" :direction="drawerDirection" :ui="{ content: 'w-72' }">
        <template #content>
            <div class="flex flex-col h-full bg-white dark:bg-[#0a0a0a]">
                <!-- Drawer header -->
                <div
                    class="h-14 flex items-center justify-between px-4 shadow-[0_1px_0_0_rgba(0,0,0,0.08)]"
                >
                    <img src="/logo.svg" alt="بنيان" class="h-7" />
                    <UButton
                        icon="i-heroicons-x-mark"
                        variant="ghost"
                        color="neutral"
                        size="sm"
                        :aria-label="t('layout.close_menu')"
                        @click="uiStore.closeDrawer()"
                    />
                </div>

                <!-- Navigation items -->
                <div class="flex-1 overflow-y-auto py-3">
                    <UNavigationMenu orientation="vertical" :items="navigationItems" class="px-2" />
                </div>
            </div>
        </template>
    </UDrawer>
</template>
