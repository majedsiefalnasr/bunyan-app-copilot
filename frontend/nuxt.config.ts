// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: [
    '@nuxt/ui',
    '@nuxtjs/i18n',
  ],
  devtools: { enabled: true },
  compatibilityDate: '2024-04-03',
  i18n: {
    locales: ['ar', 'en'],
    defaultLocale: 'ar',
    strategy: 'prefix',
    rtl: { ar: true },
  },
})
