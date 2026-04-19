<script setup lang="ts">
  import type { Category, CategoryFormData } from '~/types';
  import CategoryTree from '~/components/categories/CategoryTree.vue';
  import CategoryFormModal from '~/components/categories/CategoryFormModal.vue';
  import CategoryBreadcrumb from '~/components/categories/CategoryBreadcrumb.vue';

  definePageMeta({
    layout: 'dashboard',
    middleware: ['auth', 'role'],
    requiredRole: 'admin',
  });

  const { t } = useI18n();
  const categoryStore = useCategoryStore();

  // State
  const isFormModalOpen = ref(false);
  const editingCategory = ref<Category | null>(null);
  const deleteConfirmId = ref<number | null>(null);
  const selectedCategoryId = ref<number | null>(null);

  // Composables
  const { notifySuccess, notifyError } = useNotification();

  /**
   * Load categories on mount
   */
  onMounted(async () => {
    try {
      await categoryStore.loadCategories({ includeInactive: true });
    } catch {
      notifyError(t('categories.feedback.errors.loadFailed'));
    }
  });

  /**
   * Open form modal for create mode
   */
  const openCreateForm = () => {
    editingCategory.value = null;
    isFormModalOpen.value = true;
  };

  /**
   * Open form modal for edit mode
   */
  const openEditForm = (category: Category) => {
    editingCategory.value = category;
    isFormModalOpen.value = true;
  };

  /**
   * Close form modal
   */
  const closeFormModal = () => {
    isFormModalOpen.value = false;
    editingCategory.value = null;
  };

  /**
   * Handle form submit (create or update)
   */
  const handleFormSubmit = async (data: CategoryFormData & { id?: number; version?: number }) => {
    try {
      if (data.id) {
        // Update existing
        await categoryStore.updateCategory(data.id, {
          ...data,
          version: data.version || 0,
        });
        notifySuccess(t('categories.feedback.success.updated'));
      } else {
        // Create new
        await categoryStore.createCategory(data);
        notifySuccess(t('categories.feedback.success.created'));
      }
      closeFormModal();
    } catch {
      notifyError(t('categories.feedback.errors.submitFailed'));
    }
  };

  /**
   * Confirm and delete category
   */
  const handleDeleteConfirm = async (id: number) => {
    try {
      await categoryStore.deleteCategory(id);
      notifySuccess(t('categories.feedback.success.deleted'));
      deleteConfirmId.value = null;
    } catch {
      notifyError(t('categories.feedback.errors.deleteFailed'));
    }
  };

  /**
   * Handle tree node selection
   */
  const handleSelectCategory = (category: Category) => {
    selectedCategoryId.value = category.id;
    categoryStore.selectCategory(category);
  };

  /**
   * Handle edit from tree
   */
  const handleEditFromTree = (category: Category) => {
    openEditForm(category);
  };

  /**
   * Handle delete from tree
   */
  const handleDeleteFromTree = (category: Category) => {
    deleteConfirmId.value = category.id;
  };

  /**
   * Handle reorder
   */
  const handleReorder = async (categoryId: number, newSortOrder: number) => {
    try {
      const category = categoryStore.getCategoryById(categoryId);
      if (category) {
        await categoryStore.reorderCategory(categoryId, newSortOrder, category.version);
        notifySuccess(t('categories.feedback.success.reordered'));
      }
    } catch {
      notifyError(t('categories.feedback.errors.reorderFailed'));
    }
  };

  /**
   * Handle move
   */
  const handleMove = async (categoryId: number, newParentId: number | null) => {
    try {
      const category = categoryStore.getCategoryById(categoryId);
      if (category) {
        await categoryStore.moveCategory(categoryId, newParentId, category.version);
        notifySuccess(t('categories.feedback.success.moved'));
      }
    } catch {
      notifyError(t('categories.feedback.errors.moveFailed'));
    }
  };

  /**
   * Refresh categories
   */
  const handleRefresh = async () => {
    try {
      await categoryStore.loadCategories({ includeInactive: true });
      notifySuccess(t('categories.feedback.success.refreshed'));
    } catch {
      notifyError(t('categories.feedback.errors.refreshFailed'));
    }
  };

  // Get selected category for breadcrumb
  const selectedCategory = computed(
    () => categoryStore.getCategoryById(selectedCategoryId.value || 0) || null
  );
</script>

<template>
  <div class="category-management-page">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
        {{ $t('categories.title') }}
      </h1>
      <p class="text-gray-600 dark:text-gray-400">
        {{ $t('categories.description') }}
      </p>
    </div>

    <!-- Breadcrumb -->
    <CategoryBreadcrumb
      :category-id="selectedCategoryId"
      :categories="categoryStore.categories"
      class="mb-4"
      @select="handleSelectCategory"
    />

    <!-- Toolbar -->
    <div class="flex items-center justify-between mb-6 gap-3">
      <div class="flex items-center gap-3">
        <UButton icon="i-heroicons-plus-20-solid" size="md" @click="openCreateForm">
          {{ $t('categories.addCategory') }}
        </UButton>
        <UButton
          v-if="selectedCategory"
          icon="i-heroicons-pencil-20-solid"
          color="neutral"
          size="md"
          @click="openEditForm(selectedCategory)"
        >
          {{ $t('common.edit') }}
        </UButton>
        <UButton
          v-if="selectedCategory"
          icon="i-heroicons-trash-20-solid"
          color="error"
          variant="soft"
          size="md"
          @click="handleDeleteFromTree(selectedCategory)"
        >
          {{ $t('common.delete') }}
        </UButton>
      </div>

      <UButton
        icon="i-heroicons-arrow-path-20-solid"
        color="neutral"
        variant="ghost"
        :loading="categoryStore.isLoading.value"
        @click="handleRefresh"
      >
        {{ $t('common.refresh') }}
      </UButton>
    </div>

    <!-- Category Tree -->
    <UCard class="mb-6">
      <template v-if="categoryStore.isLoading" #default>
        <div class="space-y-4">
          <USkeleton class="h-8 w-full" />
          <USkeleton class="h-8 w-3/4" />
          <USkeleton class="h-8 w-1/2" />
        </div>
      </template>
      <template v-else>
        <CategoryTree
          :categories="categoryStore.categories"
          editable
          selectable
          @select="handleSelectCategory"
          @edit="handleEditFromTree"
          @delete="handleDeleteFromTree"
          @reorder="handleReorder"
          @move="handleMove"
        />
      </template>
    </UCard>

    <!-- Delete Confirmation Modal -->
    <UModal
      :model-value="!!deleteConfirmId"
      @update:model-value="(val: boolean) => !val && (deleteConfirmId.value = null)"
    >
      <UCard>
        <template #header>
          <h3 class="font-semibold">{{ $t('categories.deleteConfirm') }}</h3>
        </template>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          {{ $t('categories.deleteWarning') }}
        </p>
        <template #footer>
          <div class="flex justify-end gap-3">
            <UButton color="neutral" @click="deleteConfirmId.value = null">
              {{ $t('common.cancel') }}
            </UButton>
            <UButton
              color="error"
              @click="deleteConfirmId.value && handleDeleteConfirm(deleteConfirmId.value)"
            >
              {{ $t('common.delete') }}
            </UButton>
          </div>
        </template>
      </UCard>
    </UModal>

    <!-- Form Modal -->
    <CategoryFormModal
      :is-open="isFormModalOpen"
      :category="editingCategory"
      :parent-categories="categoryStore.categories"
      @close="closeFormModal"
      @submit="handleFormSubmit"
    />
  </div>
</template>

<style scoped>
  .category-management-page {
    padding: 1rem;
  }
</style>
