import { defineStore } from 'pinia';
import type { Project, ProjectFormData, ProjectFilters, ProjectStatus } from '~/types/project';

export const useProjectStore = defineStore('project', () => {
  const projects = ref<Project[]>([]);
  const selectedProject = ref<Project | null>(null);
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  const filters = ref<ProjectFilters>({});
  const currentPage = ref(1);
  const lastPage = ref(1);
  const total = ref(0);

  const {
    fetchProjects,
    fetchProject,
    createProject,
    updateProject,
    transitionStatus: apiTransitionStatus,
    deleteProject,
  } = useProjects();

  const loadProjects = async (page = 1) => {
    try {
      isLoading.value = true;
      error.value = null;
      const response = await fetchProjects(filters.value, page);
      projects.value = response.data;
      currentPage.value = response.meta.current_page;
      lastPage.value = response.meta.last_page;
      total.value = response.meta.total;
      return response.data;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const loadProject = async (id: number) => {
    try {
      isLoading.value = true;
      error.value = null;
      const project = await fetchProject(id);
      selectedProject.value = project;
      return project;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const create = async (data: ProjectFormData) => {
    try {
      isLoading.value = true;
      error.value = null;
      const project = await createProject(data);
      projects.value.unshift(project);
      return project;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const update = async (id: number, data: Partial<ProjectFormData>) => {
    try {
      isLoading.value = true;
      error.value = null;
      const updated = await updateProject(id, data);
      const index = projects.value.findIndex((p) => p.id === id);
      if (index !== -1) projects.value[index] = updated;
      if (selectedProject.value?.id === id) selectedProject.value = updated;
      return updated;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const transitionStatus = async (id: number, status: ProjectStatus) => {
    const project = projects.value.find((p) => p.id === id) ?? selectedProject.value;
    if (!project) throw new Error('Project not found');

    try {
      isLoading.value = true;
      error.value = null;
      const updated = await apiTransitionStatus(id, status, project.updated_at);
      const index = projects.value.findIndex((p) => p.id === id);
      if (index !== -1) projects.value[index] = updated;
      if (selectedProject.value?.id === id) selectedProject.value = updated;
      return updated;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const remove = async (id: number) => {
    try {
      isLoading.value = true;
      error.value = null;
      await deleteProject(id);
      projects.value = projects.value.filter((p) => p.id !== id);
      if (selectedProject.value?.id === id) selectedProject.value = null;
    } catch (err) {
      error.value = (err as Error).message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  };

  const setFilters = (newFilters: ProjectFilters) => {
    filters.value = newFilters;
  };

  const filteredProjects = computed(() => projects.value);
  const projectById = (id: number) => projects.value.find((p) => p.id === id);

  return {
    projects,
    selectedProject,
    isLoading,
    error,
    filters,
    currentPage,
    lastPage,
    total,
    loadProjects,
    loadProject,
    create,
    update,
    transitionStatus,
    remove,
    setFilters,
    filteredProjects,
    projectById,
  };
});
