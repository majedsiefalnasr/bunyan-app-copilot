// Ambient declarations to satisfy typecheck until Nuxt auto-generated types are available
declare function defineNuxtConfig<T = unknown>(config: T): T;
declare function useRoute(): unknown;
declare function useAsyncData<T = unknown>(
  key: string,
  fetcher: () => Promise<T> | T,
  opts?: unknown
): Promise<{ data: T }>;
declare function queryCollection(name: string): unknown;
declare function createError(opts: unknown): unknown;
declare function ref<T = unknown>(value?: T): { value: T | undefined };

declare module '*.vue' {
  import type { DefineComponent } from 'vue';
  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;
  export default component;
}
