---
name: nuxt-frontend-engineering
description: Nuxt.js frontend engineering patterns and conventions
---

# Nuxt.js Frontend Engineering — Bunyan

> **Design source of truth**: `DESIGN.md` — Vercel-inspired visual language (Geist fonts, shadow-as-border, achromatic palette)

## Directory Structure

```
frontend/
├── app.vue                 # Root app component
├── nuxt.config.ts          # Nuxt configuration
├── app.config.ts           # Nuxt UI color config
├── assets/css/             # Tailwind v4 styles
├── components/
│   ├── common/             # Shared components (AppHeader, AppFooter, etc.)
│   ├── project/            # Project-related components
│   ├── order/              # Order components
│   └── ui/                 # Base UI components
├── composables/
│   ├── useApi.ts           # API client composable
│   ├── useAuth.ts          # Authentication composable
│   └── useRbac.ts          # Role-based access composable
├── layouts/
│   ├── default.vue         # Main layout with sidebar
│   ├── auth.vue            # Login/register layout
│   └── dashboard.vue       # Dashboard layout
├── middleware/
│   ├── auth.ts             # Auth guard
│   ├── guest.ts            # Guest-only guard
│   └── role.ts             # RBAC middleware
├── pages/
│   ├── index.vue           # Landing page
│   ├── auth/
│   │   ├── login.vue
│   │   └── register.vue
│   ├── dashboard/          # Role-based dashboards
│   │   └── index.vue
│   ├── projects/
│   │   ├── index.vue
│   │   ├── [id].vue
│   │   └── create.vue
│   ├── orders/
│   │   ├── index.vue
│   │   └── [id].vue
│   └── products/
│       └── index.vue
├── stores/
│   ├── auth.ts             # Auth store (Pinia)
│   ├── project.ts          # Project store
│   └── cart.ts             # Shopping cart store
├── types/
│   └── index.ts            # TypeScript types
└── utils/
    ├── formatters.ts       # Date, currency formatters
    └── validators.ts       # Shared validation schemas
```

## API Composable Pattern

```typescript
// composables/useApi.ts
export function useApi() {
  const config = useRuntimeConfig();
  const auth = useAuthStore();

  const apiFetch = $fetch.create({
    baseURL: config.public.apiBaseUrl,
    headers: {
      Accept: 'application/json',
      'Accept-Language': 'ar',
    },
    onRequest({ options }) {
      if (auth.token) {
        options.headers.set('Authorization', `Bearer ${auth.token}`);
      }
    },
    onResponseError({ response }) {
      if (response.status === 401) {
        auth.logout();
        navigateTo('/auth/login');
      }
    },
  });

  return { apiFetch };
}
```

## RBAC Middleware

```typescript
// middleware/role.ts
export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore();
  const requiredRoles = to.meta.roles as string[] | undefined;

  if (!requiredRoles || requiredRoles.length === 0) return;

  if (!auth.user || !requiredRoles.includes(auth.user.role)) {
    return navigateTo('/dashboard');
  }
});
```

## Page with RBAC

```vue
<script setup lang="ts">
definePageMeta({
  middleware: ['auth', 'role'],
  roles: ['customer', 'admin'],
});

const { apiFetch } = useApi();
const { data: projects, status } = await useAsyncData('projects', () =>
  apiFetch('/api/v1/projects')
);
</script>

<template>
  <div class="max-w-7xl mx-auto px-4">
    <h1 class="text-3xl font-semibold text-[#171717] tracking-tight mb-6">مشاريعي</h1>
    <div v-if="status === 'pending'" class="flex justify-center py-12">
      <USkeleton class="h-48 w-full" />
    </div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <ProjectCard v-for="project in projects?.data" :key="project.id" :project="project" />
    </div>
  </div>
</template>
```

## Pinia Store Pattern

```typescript
// stores/auth.ts
export const useAuthStore = defineStore(
  'auth',
  () => {
    const user = ref<User | null>(null);
    const token = ref<string | null>(null);

    const isAuthenticated = computed(() => !!token.value);
    const isAdmin = computed(() => user.value?.role === 'admin');
    const isCustomer = computed(() => user.value?.role === 'customer');

    async function login(credentials: LoginCredentials) {
      const { apiFetch } = useApi();
      const response = await apiFetch('/api/v1/auth/login', {
        method: 'POST',
        body: credentials,
      });
      token.value = response.data.token;
      user.value = response.data.user;
    }

    function logout() {
      user.value = null;
      token.value = null;
      navigateTo('/auth/login');
    }

    return { user, token, isAuthenticated, isAdmin, isCustomer, login, logout };
  },
  {
    persist: true,
  }
);
```

## RTL & Nuxt UI Configuration

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  modules: ['@nuxt/ui', '@nuxtjs/i18n', '@pinia/nuxt'],
  app: {
    head: {
      htmlAttrs: { dir: 'rtl', lang: 'ar' },
    },
  },
});
```

Nuxt UI handles RTL via Tailwind logical properties automatically. No Bootstrap imports needed.
