// frontend/composables/usePasswordToggle.ts
import { ref, computed } from 'vue';

/**
 * Password visibility toggle composable
 * Tracks show/hide state for password fields
 */
export function usePasswordToggle() {
  const isVisible = ref(false);

  const toggle = () => {
    isVisible.value = !isVisible.value;
  };

  const show = () => {
    isVisible.value = true;
  };

  const hide = () => {
    isVisible.value = false;
  };

  const type = computed(() => (isVisible.value ? 'text' : 'password'));

  const icon = computed(() =>
    isVisible.value ? 'i-heroicons-eye-slash-20-solid' : 'i-heroicons-eye-20-solid'
  );

  const ariaLabel = computed(() => (isVisible.value ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'));

  return {
    isVisible,
    toggle,
    show,
    hide,
    type,
    icon,
    ariaLabel,
  };
}
