<script setup lang="ts">
import type { Category } from '~/types/categories';

interface Props {
  categoryId?: number | null;
  categories: Category[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
  (e: 'select', category: Category | null): void;
}>();

// Find and build path from root to category
const breadcrumbPath = computed(() => {
  if (!props.categoryId) {
    return [];
  }

  const path: Category[] = [];
  const findPath = (items: Category[], targetId: number, current: Category[]): boolean => {
    for (const item of items) {
      const newPath = [...current, item];
      if (item.id === targetId) {
        path.push(...newPath);
        return true;
      }
      if (item.children && findPath(item.children, targetId, newPath)) {
        return true;
      }
    }
    return false;
  };

  findPath(props.categories, props.categoryId, []);
  return path;
});

const navigateTo = (category: Category | null) => {
  // This could emit an event or use composables to navigate
  // For now, we'll just emit
  emit('select', category);
};
</script>

<template>
  <div class="category-breadcrumb flex items-center gap-1 text-sm py-2">
    <!-- Home Link -->
    <button
      class="flex items-center gap-1 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors"
      @click="navigateTo(null)"
    >
      <Icon name="i-heroicons-home-20-solid" class="w-4 h-4" />
      <span class="hidden sm:inline">{{ $t('common.home') }}</span>
    </button>

    <!-- Breadcrumb Path -->
    <template v-for="(ancestor, index) in breadcrumbPath" :key="ancestor.id">
      <span class="text-gray-400 dark:text-gray-600">/</span>
      <button
        class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 transition-colors truncate"
        :class="{ 'font-semibold text-gray-900 dark:text-gray-100 pointer-events-none': index === breadcrumbPath.length - 1 }"
        @click="navigateTo(ancestor)"
      >
        {{ ancestor.name_ar }}
      </button>
    </template>
  </div>
</template>

<style scoped>
.category-breadcrumb {
  overflow-x: auto;
  padding-right: 0.5rem;
}

/* RTL support - when dir=rtl, breadcrumb is already reversed by HTML dir attribute */
</style>
