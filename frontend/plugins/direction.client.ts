// frontend/plugins/direction.client.ts
// Client-only plugin: applies stored direction preference to <html dir>
// BEFORE Vue hydration to prevent CLS (layout shift)
export default defineNuxtPlugin(() => {
  if (import.meta.client) {
    const stored = localStorage.getItem('bunyan_direction');
    if (stored === 'rtl' || stored === 'ltr') {
      document.documentElement.dir = stored;
    }
    // Default 'rtl' is already set via nuxt.config.ts app.head.htmlAttrs
  }
});
