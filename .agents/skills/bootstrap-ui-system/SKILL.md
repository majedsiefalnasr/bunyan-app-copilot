---
name: bootstrap-ui-system
description: Nuxt UI (@nuxt/ui) component library, Tailwind v4, RTL, DESIGN.md visual language
---

# Nuxt UI System — Bunyan

> **Design source of truth**: `DESIGN.md` (project root) — Vercel-inspired visual language

## Setup

```bash
npx nuxi@latest module add ui
```

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  modules: ['@nuxt/ui'],
  app: {
    head: {
      htmlAttrs: { dir: 'rtl', lang: 'ar' },
    },
  },
});
```

## Design System Integration

All UI code MUST follow `DESIGN.md`. Key rules:

- **Fonts**: Geist Sans (body) / Geist Mono (code)
- **Shadow-as-border**: `box-shadow: 0px 0px 0px 1px rgba(0,0,0,0.08)` — NO CSS `border`
- **Primary text**: `#171717` (NOT `#000000`)
- **Weights**: 400 (body), 500 (UI labels/buttons), 600 (headings) — never 700 on body
- **Letter-spacing**: Negative at display sizes (e.g., -2.4px at 48px)
- **Border-radius scale**: 2px (code) → 6px (buttons) → 8px (cards) → 12px (images) → 9999px (pills)

### Color Palette (from DESIGN.md)

Configure via `app.config.ts`:

```typescript
// app.config.ts
export default defineAppConfig({
  ui: {
    colors: {
      primary: 'neutral', // achromatic palette
      neutral: 'neutral',
    },
  },
});
```

Functional accents (workflow only):

- Ship Red `#ff5b4f` | Preview Pink `#de1d8d` | Develop Blue `#0a72ef`

## Component Patterns

### Card (shadow-as-border, no CSS border)

```vue
<template>
  <UCard
    class="rounded-lg"
    :ui="{
      root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]',
      body: 'bg-white',
    }"
  >
    <template #header>
      <h3 class="text-[#171717] font-semibold tracking-tight">{{ title }}</h3>
    </template>
    <slot />
  </UCard>
</template>
```

### Button

```vue
<template>
  <UButton label="إنشاء مشروع" color="neutral" class="rounded-[6px] text-sm font-medium" />
  <UButton
    label="إلغاء"
    variant="outline"
    class="rounded-[6px] shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08)]"
  />
</template>
```

### Form with Validation (Nuxt UI + Zod)

```vue
<script setup lang="ts">
import { z } from 'zod';
import type { FormSubmitEvent } from '#ui/types';

const schema = z.object({
  name: z.string().min(1, 'اسم المشروع مطلوب'),
  budget: z.number().min(0, 'الميزانية يجب أن تكون أكبر من صفر'),
  location: z.string().min(1, 'الموقع مطلوب'),
});

type Schema = z.output<typeof schema>;
const state = reactive<Partial<Schema>>({});

async function onSubmit(event: FormSubmitEvent<Schema>) {
  // handle submit
}
</script>

<template>
  <UForm :schema="schema" :state="state" @submit="onSubmit">
    <UFormField label="اسم المشروع" name="name">
      <UInput v-model="state.name" />
    </UFormField>
    <UFormField label="الميزانية" name="budget">
      <UInput v-model.number="state.budget" type="number" />
    </UFormField>
    <UButton type="submit" label="حفظ" />
  </UForm>
</template>
```

### Dashboard Stats

```vue
<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
    <UCard
      v-for="stat in stats"
      :key="stat.label"
      class="shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]"
    >
      <div class="flex items-center gap-3">
        <div :class="['rounded-lg p-3', stat.bgClass]">
          <UIcon :name="stat.icon" class="text-xl" />
        </div>
        <div>
          <p class="text-sm text-[#666666]">{{ stat.label }}</p>
          <p class="text-2xl font-semibold text-[#171717] tracking-tight">{{ stat.value }}</p>
        </div>
      </div>
    </UCard>
  </div>
</template>
```

## RTL Rules (Tailwind Logical Properties)

Nuxt UI + Tailwind CSS v4 use logical properties — auto-mirror in RTL:

1. Use `ms-*` / `me-*` (margin-inline-start/end)
2. Use `ps-*` / `pe-*` (padding-inline-start/end)
3. Use `text-start` / `text-end`
4. Use `start-0` / `end-0` for positioning
5. Icons with directional meaning must be mirrored via `rtl:rotate-180`
6. Nuxt UI components handle RTL automatically

## Mandatory Component Library

Always use Nuxt UI components before writing custom HTML:

- Layout: `UDashboardLayout`, `UDashboardSidebar`, `UDashboardPanel`
- Data: `UTable`, `UCard`, `UBadge`, `UAvatar`
- Forms: `UForm`, `UFormField`, `UInput`, `USelect`, `UTextarea`, `UCheckbox`
- Actions: `UButton`, `UDropdownMenu`, `UModal`, `UDrawer`
- Navigation: `UTabs`, `UBreadcrumb`, `UPagination`
- Feedback: `UAlert`, `UToast`, `USkeleton`

**Never use Bootstrap classes** — Nuxt UI + Tailwind CSS v4 only.

Docs: https://ui.nuxt.com | MCP: https://mcp.nuxt.com

## Accessibility

- All form inputs must have labels (Nuxt UI `UFormField` handles this)
- Use `aria-label` for icon-only buttons
- Color contrast ratio ≥ 4.5:1
- Use `USkeleton` for loading states
- Use Nuxt UI built-in a11y attributes
