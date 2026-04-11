// Ambient declarations to satisfy typecheck until Nuxt auto-generated types are available
declare function defineNuxtConfig<T = any>(config: T): T;
declare function useRoute(): any;
declare function useAsyncData<T = any>(
  key: string,
  fetcher: () => Promise<T> | T,
  opts?: any
): Promise<{ data: any }>;
declare function queryCollection(name: string): any;
declare function createError(opts: any): any;
declare const ref: any;

declare module '*.vue' {
  import type { DefineComponent } from 'vue';
  const component: DefineComponent<{}, {}, any>;
  export default component;
}
