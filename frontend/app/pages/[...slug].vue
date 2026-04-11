<script setup lang="ts">
import type { RouteLocationNormalizedLoaded } from 'vue-router';
const route = useRoute() as RouteLocationNormalizedLoaded;

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
