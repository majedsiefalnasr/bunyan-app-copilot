<script setup lang="ts">
import { useBreadcrumb } from '../../../composables/useBreadcrumb';

const { breadcrumbs } = useBreadcrumb();
</script>

<template>
  <UBreadcrumb v-if="breadcrumbs.length" :items="breadcrumbs" class="px-4 py-2" />
</template>
