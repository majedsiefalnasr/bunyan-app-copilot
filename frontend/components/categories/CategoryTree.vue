<script setup lang="ts">
  import type { Category } from '~/types';
  import CategoryTreeNode from './CategoryTreeNode.vue';

  interface Props {
    categories: Category[];
    editable?: boolean;
    selectable?: boolean;
    onSelect?: (category: Category) => void;
    onEdit?: (category: Category) => void;
    onDelete?: (category: Category) => void;
    onReorder?: (categoryId: number, newSortOrder: number) => void;
    onMove?: (categoryId: number, newParentId: number | null) => void;
  }

  defineProps<Props>();

  const expandedIds = ref<Set<number>>(new Set());

  const toggleExpanded = (id: number) => {
    if (!expandedIds.value) {
      expandedIds.value = new Set();
    }
    if (expandedIds.value.has(id)) {
      expandedIds.value.delete(id);
    } else {
      expandedIds.value.add(id);
    }
  };

  const isExpanded = (id: number) => expandedIds.value?.has(id) ?? false;
</script>

<template>
  <div class="category-tree" dir="auto">
    <div v-if="categories.length === 0" class="text-center py-8 text-gray-500">
      <p>{{ $t('categories.noCategories') }}</p>
    </div>
    <ul v-else class="space-y-1">
      <li v-for="category in categories" :key="category.id">
        <CategoryTreeNode
          :category="category"
          :level="0"
          :editable="editable"
          :selectable="selectable"
          :expanded="isExpanded(category.id)"
          @select="onSelect"
          @edit="onEdit"
          @delete="onDelete"
          @toggle-expanded="toggleExpanded"
          @reorder="onReorder"
          @move="onMove"
        />
      </li>
    </ul>
  </div>
</template>

<style scoped>
  .category-tree {
    user-select: none;
  }

  .category-tree ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }
</style>
