import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';

/**
 * Composable for locale-aware routing
 * Automatically includes the current locale in navigation
 */
export function useLocaleRoute() {
    const router = useRouter();
    const { locale } = useI18n();

    /**
     * Navigate to a path with the current locale prefix
     */
    const push = (path: string) => {
        // Normalize path
        const normalizedPath = path.startsWith('/') ? path : `/${path}`;

        // If path already has locale prefix, use it as is
        if (/^\/(ar|en)(\/|$)/.test(normalizedPath)) {
            return router.push(normalizedPath);
        }

        // Add current locale prefix if not at root
        if (normalizedPath === '/' || normalizedPath === '') {
            return router.push(`/${locale.value}`);
        }

        return router.push(`/${locale.value}${normalizedPath}`);
    };

    /**
     * Go back to previous page
     */
    const back = () => {
        router.back();
    };

    return { push, back };
}
