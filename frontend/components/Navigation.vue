<template>
  <nav class="bg-white border-b border-gray-200 dark:bg-[#0a0a0a] dark:border-[#2a2a2a]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <NuxtLink to="/" class="flex items-center space-x-2">
          <div class="text-xl font-bold text-[#171717] dark:text-white">Bunyan</div>
        </NuxtLink>

        <!-- Center Navigation (Role-based) -->
        <div class="hidden md:flex space-x-6" dir="auto">
          <template v-if="isAuthenticated && authStore.user">
            <!-- Customer Navigation -->
            <template v-if="authStore.user.role === 'customer'">
              <NuxtLink
                to="/dashboard"
                class="text-sm text-[#666666] hover:text-[#171717] dark:hover:text-white"
              >
                {{ $t('nav.dashboard') }}
              </NuxtLink>
              <NuxtLink
                to="/projects"
                class="text-sm text-[#666666] hover:text-[#171717] dark:hover:text-white"
              >
                {{ $t('nav.projects') }}
              </NuxtLink>
            </template>

            <!-- Contractor Navigation -->
            <template v-if="authStore.user.role === 'contractor'">
              <NuxtLink
                to="/dashboard"
                class="text-sm text-[#666666] hover:text-[#171717] dark:hover:text-white"
              >
                {{ $t('nav.dashboard') }}
              </NuxtLink>
              <NuxtLink
                to="/projects"
                class="text-sm text-[#666666] hover:text-[#171717] dark:hover:text-white"
              >
                {{ $t('nav.my_projects') }}
              </NuxtLink>
              <NuxtLink
                to="/earnings"
                class="text-sm text-[#666666] hover:text-[#171717] dark:hover:text-white"
              >
                {{ $t('nav.earnings') }}
              </NuxtLink>
            </template>

            <!-- Admin Navigation -->
            <template v-if="authStore.user.role === 'admin'">
              <NuxtLink
                to="/admin"
                class="text-sm text-[#666666] hover:text-[#171717] dark:hover:text-white"
              >
                {{ $t('nav.admin') }}
              </NuxtLink>
              <NuxtLink
                to="/analytics"
                class="text-sm text-[#666666] hover:text-[#171717] dark:hover:text-white"
              >
                {{ $t('nav.analytics') }}
              </NuxtLink>
            </template>
          </template>
        </div>

        <!-- Right Navigation -->
        <div class="flex items-center spaces-x-2">
          <!-- Language Switcher -->
          <UButton
            icon="i-heroicons-language-20-solid"
            color="gray"
            variant="ghost"
            size="sm"
            :label="locale.value === 'ar' ? 'En' : 'ع'"
            @click="toggleLanguage"
          />

          <!-- User Menu / Auth Links -->
          <div v-if="isAuthenticated" class="flex items-center space-x-4">
            <!-- Profile Link -->
            <NuxtLink
              :to="`/${locale.value}/profile`"
              class="text-sm text-[#666666] hover:text-[#171717] dark:hover:text-white"
            >
              {{ authStore.user?.firstName }}
            </NuxtLink>

            <!-- Logout Button -->
            <UButton :label="$t('nav.logout')" color="red" size="sm" @click="onLogout" />
          </div>

          <!-- Login/Register Links (Not Authenticated) -->
          <div v-else class="flex items-center space-x-3">
            <NuxtLink
              :to="`/${locale.value}/auth/login`"
              class="text-sm font-medium text-[#171717] hover:text-[#666666] dark:text-white"
            >
              {{ $t('nav.login') }}
            </NuxtLink>
            <NuxtLink
              :to="`/${locale.value}/auth/register`"
              class="px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
            >
              {{ $t('nav.register') }}
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
  import { computed } from 'vue';
  import { useI18n } from 'vue-i18n';
  import { useRouter } from 'vue-router';
  import { useAuthStore } from '~/stores/auth';
  import { useAuth } from '~/composables/useAuth';

  const router = useRouter();
  const { locale } = useI18n();
  const authStore = useAuthStore();
  const auth = useAuth();

  const isAuthenticated = computed(() => !!authStore.token && !!authStore.user);

  const toggleLanguage = () => {
    const newLocale = locale.value === 'ar' ? 'en' : 'ar';
    locale.value = newLocale;
    // Update HTML dir attribute
    document.documentElement.dir = newLocale === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = newLocale;
  };

  const onLogout = async () => {
    await auth.logout();
    await router.push(`/${locale.value}/auth/login`);
  };
</script>

<style scoped>
  /* Shadow-as-border for nav */
  nav {
    box-shadow:
      0px 0px 0px 1px rgba(0, 0, 0, 0.08),
      0px 2px 2px rgba(0, 0, 0, 0.04);
  }
</style>
