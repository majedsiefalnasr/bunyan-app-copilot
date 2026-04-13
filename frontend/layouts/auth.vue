<script setup lang="ts">
  // Auth layout: minimal centered layout with RTL support.
  // Page-level guest/auth middleware handles redirection — no watcher needed here.
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#fafafa] dark:bg-[#0a0a0a] p-4">
    <slot />
  </div>
</template>
