import type { Category, CategoryFormData } from '~/types';

export function useCategories() {
  const { apiFetch } = useApi();

  const categories = ref<Category[]>([]);
  const selectedCategory = ref<Category | null>(null);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  /**
   * Fetch all categories as tree structure
   */
  async function fetchCategories(options?: {
    includeInactive?: boolean;
    includeDeleted?: boolean;
  }) {
    isLoading.value = true;
    error.value = null;

    try {
      const params = new URLSearchParams();
      if (options?.includeInactive) params.append('include_inactive', 'true');
      if (options?.includeDeleted) params.append('include_deleted', 'true');

      const response = await apiFetch<{ success: boolean; data: Category[] }>(
        `/api/v1/categories${params.toString() ? `?${params.toString()}` : ''}`,
        { method: 'GET' }
      );

      if (response.success) {
        categories.value = response.data;
        return response.data;
      } else {
        throw new Error('Failed to fetch categories');
      }
    } catch (err) {
      error.value = (err as Error).message;
      console.error('Error fetching categories:', err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  /**
   * Fetch single category by ID
   */
  async function fetchCategory(id: number) {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await apiFetch<{ success: boolean; data: Category }>(
        `/api/v1/categories/${id}`,
        { method: 'GET' }
      );

      if (response.success) {
        return response.data;
      } else {
        throw new Error('Failed to fetch category');
      }
    } catch (err) {
      error.value = (err as Error).message;
      console.error('Error fetching category:', err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  /**
   * Create new category
   */
  async function createCategory(data: CategoryFormData) {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await apiFetch<{ success: boolean; data: Category }>('/api/v1/categories', {
        method: 'POST',
        body: data,
      });

      if (response.success) {
        // Refresh tree after creation
        await fetchCategories();
        return response.data;
      } else {
        throw new Error('Failed to create category');
      }
    } catch (err) {
      error.value = (err as Error).message;
      console.error('Error creating category:', err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  /**
   * Update existing category
   */
  async function updateCategory(id: number, data: Partial<CategoryFormData> & { version: number }) {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await apiFetch<{ success: boolean; data: Category }>(
        `/api/v1/categories/${id}`,
        {
          method: 'PUT',
          body: data,
        }
      );

      if (response.success) {
        // Refresh tree after update
        await fetchCategories();
        return response.data;
      } else {
        throw new Error('Failed to update category');
      }
    } catch (err) {
      error.value = (err as Error).message;
      console.error('Error updating category:', err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  /**
   * Reorder category within siblings
   */
  async function reorderCategory(id: number, newSortOrder: number, version: number) {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await apiFetch<{ success: boolean; data: Category }>(
        `/api/v1/categories/${id}/reorder`,
        {
          method: 'PUT',
          body: { sort_order: newSortOrder, version },
        }
      );

      if (response.success) {
        // Refresh tree after reorder
        await fetchCategories();
        return response.data;
      } else {
        throw new Error('Failed to reorder category');
      }
    } catch (err) {
      error.value = (err as Error).message;
      console.error('Error reordering category:', err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  /**
   * Move category to different parent
   */
  async function moveCategory(id: number, newParentId: number | null, version: number) {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await apiFetch<{ success: boolean; data: Category }>(
        `/api/v1/categories/${id}/move`,
        {
          method: 'PUT',
          body: { parent_id: newParentId, version },
        }
      );

      if (response.success) {
        // Refresh tree after move
        await fetchCategories();
        return response.data;
      } else {
        throw new Error('Failed to move category');
      }
    } catch (err) {
      error.value = (err as Error).message;
      console.error('Error moving category:', err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  /**
   * Delete (soft-delete) category
   */
  async function deleteCategory(id: number) {
    isLoading.value = true;
    error.value = null;

    try {
      const response = await apiFetch<{ success: boolean }>(`/api/v1/categories/${id}`, {
        method: 'DELETE',
      });

      if (response.success) {
        // Refresh tree after delete
        await fetchCategories();
        return true;
      } else {
        throw new Error('Failed to delete category');
      }
    } catch (err) {
      error.value = (err as Error).message;
      console.error('Error deleting category:', err);
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  return {
    // State
    categories,
    selectedCategory,
    isLoading,
    error,
    // Methods
    fetchCategories,
    fetchCategory,
    createCategory,
    updateCategory,
    reorderCategory,
    moveCategory,
    deleteCategory,
  };
}
