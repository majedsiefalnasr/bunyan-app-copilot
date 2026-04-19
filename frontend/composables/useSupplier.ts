import type {
  PaginatedSuppliers,
  StoreSupplierPayload,
  SupplierListFilters,
  SupplierProfile,
  UpdateSupplierPayload,
} from '~/types/supplier';

interface ApiResponse<T> {
  success: boolean;
  data: T;
  error: null | {
    code: string;
    message: string;
    details: Record<string, string[]> | null;
  };
}

export function useSupplier() {
  const { apiFetch } = useApi();

  async function list(filters: SupplierListFilters = {}): Promise<PaginatedSuppliers> {
    const params = new URLSearchParams();
    if (filters.city) params.set('city', filters.city);
    if (filters.district) params.set('district', filters.district);
    if (filters.search) params.set('search', filters.search);
    if (filters.verification_status) params.set('verification_status', filters.verification_status);
    if (filters.per_page) params.set('per_page', String(filters.per_page));
    if (filters.page) params.set('page', String(filters.page));

    const qs = params.toString();
    const url = `/api/v1/suppliers${qs ? `?${qs}` : ''}`;

    const response = await apiFetch<PaginatedSuppliers>(url);
    return response;
  }

  async function show(id: number): Promise<SupplierProfile> {
    const response = await apiFetch<ApiResponse<SupplierProfile>>(`/api/v1/suppliers/${id}`);
    return response.data;
  }

  async function store(payload: StoreSupplierPayload): Promise<SupplierProfile> {
    const response = await apiFetch<ApiResponse<SupplierProfile>>('/api/v1/suppliers', {
      method: 'POST',
      body: payload,
    });
    return response.data;
  }

  async function update(id: number, payload: UpdateSupplierPayload): Promise<SupplierProfile> {
    const response = await apiFetch<ApiResponse<SupplierProfile>>(`/api/v1/suppliers/${id}`, {
      method: 'PUT',
      body: payload,
    });
    return response.data;
  }

  async function verify(id: number): Promise<SupplierProfile> {
    const response = await apiFetch<ApiResponse<SupplierProfile>>(
      `/api/v1/suppliers/${id}/verify`,
      { method: 'PUT' }
    );
    return response.data;
  }

  async function suspend(id: number): Promise<SupplierProfile> {
    const response = await apiFetch<ApiResponse<SupplierProfile>>(
      `/api/v1/suppliers/${id}/suspend`,
      { method: 'PUT' }
    );
    return response.data;
  }

  async function listProducts(id: number, page = 1, perPage = 15) {
    const response = await apiFetch<PaginatedSuppliers>(
      `/api/v1/suppliers/${id}/products?page=${page}&per_page=${perPage}`
    );
    return response;
  }

  return { list, show, store, update, verify, suspend, listProducts };
}
