<script setup lang="ts">
    import type { RouteLocationNormalizedLoaded } from 'vue-router';
    const route = useRoute() as RouteLocationNormalizedLoaded;

    // Handle specific error display routes
    const pathWithoutLocale = route.path.replace(/^(\/ar|\/en)\/?/, '').replace(/\/$/, '');

    if (pathWithoutLocale === '/not-found' || route.path.endsWith('/not-found')) {
        // Show 404 error page
        throw createError({ statusCode: 404, statusMessage: 'Not Found', fatal: true });
    } else if (pathWithoutLocale === '/access-denied' || route.path.endsWith('/access-denied')) {
        // Show 403 error page
        throw createError({ statusCode: 403, statusMessage: 'Access Denied', fatal: true });
    } else if (pathWithoutLocale === '/server-error' || route.path.endsWith('/server-error')) {
        // Show 500 error page
        throw createError({ statusCode: 500, statusMessage: 'Server Error', fatal: true });
    }

    type ContentPage = {
        title?: string;
        body?: string;
        slug?: string;
    } & Record<string, unknown>;

    const { data: page } = await useAsyncData<ContentPage | null>('page-' + route.path, () => {
        return queryCollection('content')
            .path(route.path as string)
            .first() as Promise<ContentPage | null>;
    });

    if (!page?.value) {
        throw createError({ statusCode: 404, statusMessage: 'Page not found', fatal: true });
    }
</script>

<template>
    <ContentRenderer v-if="page?.value" :value="page?.value" />
</template>
