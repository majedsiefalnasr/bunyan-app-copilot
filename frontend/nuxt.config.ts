// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: ['@nuxt/ui', '@nuxtjs/i18n'],
  devtools: { enabled: true },
  compatibilityDate: '2024-04-03',
  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8000',
    },
  },
  colorMode: {
    classSuffix: '',
  },
  app: {
    head: {
      htmlAttrs: { lang: 'ar', dir: 'rtl' },
    },
  },
  i18n: {
    defaultLocale: 'ar',
    strategy: 'prefix',
    langDir: 'locales',
    locales: [
      { code: 'ar', language: 'ar-SA', dir: 'rtl', file: 'ar.json' },
      { code: 'en', language: 'en-US', dir: 'ltr', file: 'en.json' },
    ],
  },
});
