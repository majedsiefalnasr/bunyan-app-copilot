<script setup lang="ts">
import type { RouteLocationNormalizedLoaded } from 'vue-router';
const route = useRoute() as RouteLocationNormalizedLoaded;

const { data: page } = await useAsyncData<unknown | null>('page-' + route.path, () => {
  return queryCollection('content')
    .path(route.path as string)
    .first();
});

if (!page.value) {
  throw createError({ statusCode: 404, statusMessage: 'Page not found', fatal: true });
}
</script>

<template>
  <ContentRenderer v-if="page" :value="page" />
</template>
