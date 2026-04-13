import { nextTick } from 'vue';
import { useI18n } from 'vue-i18n';

export default defineNuxtRouteMiddleware(async (to, _from) => {
  if (import.meta.server) return;

  const { locale, setLocale } = useI18n();

  const updateDirAttribute = async (currentLocale: string) => {
    await nextTick();
    const dir = currentLocale === 'ar' ? 'rtl' : 'ltr';
    const lang = currentLocale === 'ar' ? 'ar-SA' : 'en-US';

    document.documentElement.setAttribute('dir', dir);
    document.documentElement.setAttribute('lang', lang);
  };

  // Extract locale from route
  const pathLocale = to.path.split('/')[1];
  if (pathLocale === 'ar' || pathLocale === 'en') {
    if (locale.value !== pathLocale) {
      await setLocale(pathLocale);
    }
    await updateDirAttribute(pathLocale);
  } else {
    // Use default or current locale
    await updateDirAttribute(locale.value);
  }
});
