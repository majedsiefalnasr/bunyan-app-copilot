// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: ['@nuxt/ui', '@nuxtjs/i18n'],
  devtools: { enabled: true },
  compatibilityDate: '2024-04-03',
  app: {
    head: {
      htmlAttrs: {
        lang: 'ar',
      },
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
