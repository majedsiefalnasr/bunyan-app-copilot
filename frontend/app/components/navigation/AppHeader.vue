<script setup lang="ts">
  import { computed } from 'vue';
  import { useAuth } from '../../../composables/useAuth';
  import { usePreferences } from '../../../composables/usePreferences';
  import type { DropdownMenuGroup } from '../../../types/index';

  withDefaults(
    defineProps<{
      showHamburger?: boolean;
      title?: string;
    }>(),
    { showHamburger: true, title: '' }
  );

  const emit = defineEmits<{
    'hamburger-click': [];
  }>();

  const { t, locale } = useI18n();
  const { user, isAuthenticated, logout } = useAuth();
  const { direction, toggleDirection, toggleColorMode, setLocale } = usePreferences();

  const directionAriaLabel = computed(() =>
    direction.value === 'rtl' ? t('layout.direction_ltr') : t('layout.direction_rtl')
  );

  const userMenuItems = computed<DropdownMenuGroup[]>(() => [
    {
      items: [
        {
          label: t('profile'),
          icon: 'i-heroicons-user',
          to: `/${locale.value}/profile`,
        },
        {
          label: t('settings'),
          icon: 'i-heroicons-cog-6-tooth',
          to: `/${locale.value}/settings`,
        },
      ],
    },
    {
      items: [
        {
          label: t('logout'),
          icon: 'i-heroicons-arrow-right-on-rectangle',
          onSelect: logout,
        },
      ],
    },
  ]);

  function switchLanguage() {
    const newLocale = locale.value === 'ar' ? 'en' : 'ar';
    setLocale(newLocale as 'ar' | 'en');
  }
</script>

<template>
  <header
    class="sticky top-0 z-40 flex h-14 shrink-0 items-center bg-white dark:bg-[#0a0a0a] shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08)] px-4"
  >
    <!-- Hamburger (mobile only) -->
    <UButton
      v-if="showHamburger"
      icon="i-heroicons-bars-3"
      variant="ghost"
      color="neutral"
      class="md:hidden me-2"
      :aria-label="t('layout.open_menu')"
      @click="emit('hamburger-click')"
    />

    <!-- Logo -->
    <NuxtLink to="/" class="flex items-center gap-2 me-auto">
      <img src="/logo.svg" alt="بنيان" class="h-7" />
    </NuxtLink>

    <!-- Page title (optional, desktop) -->
    <span
      v-if="title"
      class="hidden md:block text-sm font-medium text-[#171717] dark:text-white mx-4"
    >
      {{ title }}
    </span>

    <!-- Controls cluster -->
    <div class="flex items-center gap-1 ms-auto">
      <!-- Language switcher -->
      <UButton
        variant="ghost"
        color="neutral"
        size="sm"
        :aria-label="t('layout.switch_language')"
        @click="switchLanguage"
      >
        {{ locale === 'ar' ? 'EN' : 'عر' }}
      </UButton>

      <!-- Direction toggle -->
      <UButton
        icon="i-heroicons-arrows-right-left"
        variant="ghost"
        color="neutral"
        size="sm"
        :aria-label="directionAriaLabel"
        @click="toggleDirection"
      />

      <!-- Dark mode toggle -->
      <UButton
        icon="i-heroicons-moon"
        variant="ghost"
        color="neutral"
        size="sm"
        :aria-label="t('layout.toggle_dark_mode')"
        @click="toggleColorMode"
      />

      <!-- User menu -->
      <UDropdownMenu v-if="isAuthenticated" :items="userMenuItems">
        <UButton variant="ghost" color="neutral" size="sm" class="p-0.5">
          <UAvatar :alt="user?.name ?? 'User'" size="xs" />
        </UButton>
      </UDropdownMenu>
    </div>
  </header>
</template>
