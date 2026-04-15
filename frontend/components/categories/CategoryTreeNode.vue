<script setup lang="ts">
  import type { Category } from '~/types/categories';
  import CategoryTreeNode from './CategoryTreeNode.vue';

  interface Props {
    category: Category;
    level: number;
    editable?: boolean;
    selectable?: boolean;
    expanded?: boolean;
  }

  interface Emits {
    (e: 'select' | 'edit' | 'delete', category: Category): void;
    (e: 'toggleExpanded', id: number): void;
    (e: 'reorder', categoryId: number, newSortOrder: number): void;
    (e: 'move', categoryId: number, newParentId: number | null): void;
  }

  const props = defineProps<Props>();
  const emit = defineEmits<Emits>();

  const hasChildren = computed(() => props.category.children && props.category.children.length > 0);

  // Calculate indentation
  const paddingStart = computed(() => `${props.level * 1.5}rem`);

  const handleSelect = () => {
    emit('select', props.category);
  };

  const handleEdit = (e: Event) => {
    e.stopPropagation();
    emit('edit', props.category);
  };

  const handleDelete = (e: Event) => {
    e.stopPropagation();
    emit('delete', props.category);
  };

  const handleToggleExpanded = (e: Event) => {
    e.stopPropagation();
    emit('toggleExpanded', props.category.id);
  };
</script>

<template>
  <div
    class="category-tree-node transition-colors hover:bg-gray-50 dark:hover:bg-gray-900"
    :style="{ paddingInlineStart: paddingStart }"
  >
    <div class="flex items-center gap-2 py-2 px-3 rounded">
      <!-- Expand/Collapse Toggle -->
      <button
        v-if="hasChildren"
        class="flex-shrink-0 p-1 transition-transform"
        :class="{ 'rotate-90': expanded }"
        @click="handleToggleExpanded"
      >
        <Icon name="i-heroicons-chevron-right-20-solid" class="w-4 h-4" />
      </button>
      <div v-else class="flex-shrink-0 w-6" />

      <!-- Icon -->
      <div v-if="category.icon" class="flex-shrink-0">
        <Icon :name="category.icon" class="w-5 h-5" />
      </div>

      <!-- Category Name (clickable if selectable) -->
      <div class="flex-1 min-w-0">
        <button
          v-if="selectable"
          class="text-start font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 truncate"
          @click="handleSelect"
        >
          {{ category.name_ar }}
          <span class="text-xs text-gray-500 dark:text-gray-400 ms-2">{{ category.name_en }}</span>
        </button>
        <span v-else class="text-gray-900 dark:text-gray-100">
          {{ category.name_ar }}
          <span class="text-xs text-gray-500 dark:text-gray-400 ms-2">{{ category.name_en }}</span>
        </span>
      </div>

      <!-- Status Badge -->
      <div v-if="!category.is_active" class="flex-shrink-0">
        <span
          class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200"
        >
          {{ $t('categories.inactive') }}
        </span>
      </div>

      <!-- Edit/Delete Buttons (admin-only) -->
      <div v-if="editable" class="flex-shrink-0 flex items-center gap-1">
        <UButton
          icon="i-heroicons-pencil-20-solid"
          color="neutral"
          variant="ghost"
          size="sm"
          @click="handleEdit"
        />
        <UButton
          icon="i-heroicons-trash-20-solid"
          color="error"
          variant="ghost"
          size="sm"
          @click="handleDelete"
        />
      </div>
    </div>

    <!-- Children (recursive) -->
    <div v-if="hasChildren && expanded" class="mt-1">
      <div v-for="child in category.children" :key="child.id">
        <CategoryTreeNode
          :category="child"
          :level="level + 1"
          :editable="editable"
          :selectable="selectable"
          :expanded="expanded"
          @select="emit('select', $event)"
          @edit="emit('edit', $event)"
          @delete="emit('delete', $event)"
          @toggle-expanded="emit('toggleExpanded', $event as number)"
          @reorder="
            (categoryId: number, sortOrder: number) => emit('reorder', categoryId, sortOrder)
          "
          @move="
            (categoryId: number, parentId: number | null) => emit('move', categoryId, parentId)
          "
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
  .category-tree-node {
    position: relative;
  }
</style>
