import { defineStore } from 'pinia';
import type { Category, CategoryFormData } from '~/types';

export const useCategoryStore = defineStore('category', () => {
  // State
  const categories = ref<Category[]>([]);
  const selectedCategory = ref<Category | null>(null);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  // Composables
  const {
    fetchCategories,
    createCategory,
    updateCategory,
    reorderCategory,
    moveCategory,
    deleteCategory,
  } = useCategories();

  /**
   * Load all categories from API
   */
  const loadCategories = async (options?: {
    includeInactive?: boolean;
    includeDeleted?: boolean;
  }) => {
    try {
      isLoading.value = true;
      error.value = null;
      const data = await fetchCategories(options);
      categories.value = data;
      return data;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Select a category
   */
  const selectCategory = (category: Category | null) => {
    selectedCategory.value = category;
  };

  /**
   * Create new category
   */
  const create = async (data: CategoryFormData) => {
    try {
      isLoading.value = true;
      error.value = null;
      const created = await createCategory(data);
      // Reload categories tree
      await loadCategories();
      return created;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Update existing category
   */
  const update = async (id: number, data: Partial<CategoryFormData> & { version: number }) => {
    try {
      isLoading.value = true;
      error.value = null;
      const updated = await updateCategory(id, data);
      // Reload categories tree
      await loadCategories();
      return updated;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Reorder category
   */
  const reorder = async (id: number, newSortOrder: number, version: number) => {
    try {
      isLoading.value = true;
      error.value = null;
      const reordered = await reorderCategory(id, newSortOrder, version);
      // Reload categories tree
      await loadCategories();
      return reordered;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Move category to different parent
   */
  const move = async (id: number, newParentId: number | null, version: number) => {
    try {
      isLoading.value = true;
      error.value = null;
      const moved = await moveCategory(id, newParentId, version);
      // Reload categories tree
      await loadCategories();
      return moved;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Delete category
   */
  const delete_ = async (id: number) => {
    try {
      isLoading.value = true;
      error.value = null;
      await deleteCategory(id);
      // Reload categories tree
      await loadCategories();
      return true;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Get category by ID (from current tree)
   */
  const getCategoryById = (id: number): Category | null => {
    const findInTree = (items: Category[]): Category | null => {
      for (const item of items) {
        if (item.id === id) return item;
        if (item.children) {
          const found = findInTree(item.children);
          if (found) return found;
        }
      }
      return null;
    };
    return findInTree(categories.value ?? []);
  };

  /**
   * Get ancestors of a category (path from root to category)
   */
  const getCategoryPath = (id: number): Category[] => {
    const path: Category[] = [];
    const findAndBuildPath = (items: Category[], target: number, current: Category[]): boolean => {
      for (const item of items) {
        const newCurrent = [...current, item];
        if (item.id === target) {
          path.push(...newCurrent);
          return true;
        }
        if (item.children && findAndBuildPath(item.children, target, newCurrent)) {
          return true;
        }
      }
      return false;
    };
    findAndBuildPath(categories.value ?? [], id, []);
    return path;
  };

  // Getters
  const categoriesTree = computed(() => categories.value);

  const selectedCategoryPath = computed(() => {
    if (!selectedCategory.value) return [];
    return getCategoryPath(selectedCategory.value.id);
  });

  const isReady = computed(() => !isLoading.value && categories.value.length > 0);

  return {
    // State
    categories: categoriesTree,
    selectedCategory,
    isLoading,
    error,
    // Getters
    selectedCategoryPath,
    isReady,
    // Actions
    loadCategories,
    selectCategory,
    createCategory: create,
    updateCategory: update,
    reorderCategory: reorder,
    moveCategory: move,
    deleteCategory: delete_,
    getCategoryById,
    getCategoryPath,
  };
});
