---
name: vue
description: Vue 3 Composition API patterns
---

# Vue Skill — Bunyan

## Vue 3 Composition API Patterns

### Component Template
```vue
<script setup lang="ts">
interface Props {
  title: string
  status: ProjectStatus
}

const props = defineProps<Props>()
const emit = defineEmits<{
  statusChange: [newStatus: ProjectStatus]
}>()
</script>

<template>
  <div class="card">
    <h5>{{ props.title }}</h5>
    <button @click="emit('statusChange', 'active')">تفعيل</button>
  </div>
</template>
```

### Composable Pattern
```typescript
export function useProject(projectId: Ref<number>) {
  const { apiFetch } = useApi()
  const project = ref<Project | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function fetchProject() {
    loading.value = true
    error.value = null
    try {
      const response = await apiFetch(`/api/v1/projects/${projectId.value}`)
      project.value = response.data
    } catch (e) {
      error.value = (e as Error).message
    } finally {
      loading.value = false
    }
  }

  watch(projectId, fetchProject, { immediate: true })

  return { project, loading, error, refetch: fetchProject }
}
```

### Reactive Destructuring (Vue 3.5+)
```typescript
const { title, status } = defineProps<Props>()
// title and status are reactive refs
```

## Rules

- Use `<script setup lang="ts">` — no Options API
- Use `class` not `className`, `for` not `htmlFor`
- Use VueUse composables when available
- Props: use interface-based `defineProps<T>()`
- Emits: use typed `defineEmits<T>()`
