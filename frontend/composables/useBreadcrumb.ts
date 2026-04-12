// frontend/composables/useBreadcrumb.ts
// useState provides per-request isolation on SSR AND shared cross-instance
// reactivity on the client — satisfying both requirements without a module-level ref.
import { useRoute } from 'vue-router';
import type { BreadcrumbItem } from '../types/index';

// Declared at the module call-site using Nuxt's useState:
// - SSR: per-request state (no cross-request bleed)
// - Client: shared across all useBreadcrumb() call sites (same reactive ref)
const _manualBreadcrumbs = useState<BreadcrumbItem[] | null>('breadcrumbs.manual', () => null);

export function useBreadcrumb() {
    const route = useRoute();

    const breadcrumbs = computed<BreadcrumbItem[]>(() => {
        if (_manualBreadcrumbs.value !== null) return _manualBreadcrumbs.value;
        if (Array.isArray(route.meta.breadcrumb)) return route.meta.breadcrumb as BreadcrumbItem[];
        return [];
    });

    function setBreadcrumbs(items: BreadcrumbItem[]) {
        _manualBreadcrumbs.value = items;
    }

    function clearBreadcrumbs() {
        _manualBreadcrumbs.value = null;
    }

    return { breadcrumbs, setBreadcrumbs, clearBreadcrumbs };
}
