<template>
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile section -->
    <div class="lg:col-span-2 space-y-6">
      <UCard
        :ui="{
          root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]',
          body: 'p-6',
        }"
        class="rounded-lg"
      >
        <template #header>
          <h2 class="text-lg font-semibold text-[#171717] dark:text-white">
            {{ $t('auth.profile.title') }}
          </h2>
        </template>

        <form class="space-y-4" @submit.prevent="onSubmit">
          <!-- Error Alert -->
          <UAlert
            v-if="error"
            color="red"
            icon="i-heroicons-exclamation-circle"
            :description="error"
            @close="error = null"
          />

          <!-- Avatar Upload -->
          <div class="flex flex-col gap-3">
            <label class="text-sm font-medium text-[#171717] dark:text-white">
              {{ $t('auth.profile.avatar') }}
            </label>
            <UAvatar :src="avatarUrl" :alt="username" size="lg" class="w-20 h-20" />
            <UButton
              :label="$t('auth.profile.upload_avatar')"
              size="sm"
              variant="outline"
              @click="triggerAvatarUpload"
            />
            <input
              ref="fileInput"
              type="file"
              accept="image/*"
              style="display: none"
              @change="onAvatarChange"
            />
          </div>

          <!-- Form Fields -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <UFormGroup :label="$t('auth.profile.first_name')" :error="errors.firstName">
              <UInput v-model="form.firstName" />
            </UFormGroup>

            <UFormGroup :label="$t('auth.profile.last_name')" :error="errors.lastName">
              <UInput v-model="form.lastName" />
            </UFormGroup>

            <UFormGroup :label="$t('auth.profile.phone')" :error="errors.phone">
              <UInput v-model="form.phone" type="tel" />
            </UFormGroup>

            <UFormGroup :label="$t('auth.profile.city')" :error="errors.city">
              <USelect v-model="form.city" :options="cities" @update:model-value="onCityChange" />
            </UFormGroup>

            <UFormGroup :label="$t('auth.profile.district')" :error="errors.district">
              <USelect
                v-model="form.district"
                :options="filteredDistricts"
                :disabled="!form.city"
              />
            </UFormGroup>

            <UFormGroup :label="$t('auth.profile.language')" :error="errors.languagePreference">
              <USelect
                v-model="form.languagePreference"
                :options="[
                  { label: 'العربية / Arabic', value: 'ar' },
                  { label: 'English', value: 'en' },
                ]"
              />
            </UFormGroup>
          </div>

          <UFormGroup :label="$t('auth.profile.address')" :error="errors.address">
            <UTextarea v-model="form.address" :rows="3" />
          </UFormGroup>

          <!-- Action Buttons -->
          <div class="flex gap-3 pt-4">
            <UButton
              v-if="isDirty"
              type="submit"
              :label="$t('auth.profile.save')"
              :loading="isLoading"
              color="neutral"
            />
            <UButton
              v-if="isDirty"
              :label="$t('auth.profile.cancel')"
              variant="outline"
              @click="onCancel"
            />
          </div>
        </form>
      </UCard>
    </div>

    <!-- Sidebar Actions -->
    <div class="space-y-4">
      <UCard
        :ui="{
          root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]',
        }"
        class="rounded-lg"
      >
        <button
          class="w-full px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900 rounded-md transition"
          @click="openChangePasswordModal"
        >
          {{ $t('auth.profile.change_password') }}
        </button>
      </UCard>

      <UCard
        :ui="{
          root: 'shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08),0px_2px_2px_rgba(0,0,0,0.04)]',
        }"
        class="rounded-lg"
      >
        <button
          class="w-full px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900 rounded-md transition"
          @click="onLogout"
        >
          {{ $t('auth.logout.button') }}
        </button>
      </UCard>
    </div>

    <!-- Change Password Modal -->
    <UModal v-model="showChangePasswordModal" :title="$t('auth.change_password.title')">
      <form class="space-y-4" @submit.prevent="onChangePassword">
        <UFormGroup
          :label="$t('auth.change_password.current_password')"
          :error="passwordErrors.currentPassword"
        >
          <UInput
            v-model="passwordForm.currentPassword"
            type="password"
            icon="i-heroicons-lock-closed"
          />
        </UFormGroup>

        <UFormGroup :label="$t('auth.change_password.password')" :error="passwordErrors.password">
          <UInput
            v-model="passwordForm.password"
            type="password"
            icon="i-heroicons-lock-closed"
            @input="updatePasswordStrength"
          />
          <PasswordStrength :score="passwordStrength" class="mt-2" />
        </UFormGroup>

        <UFormGroup
          :label="$t('auth.change_password.password_confirmation')"
          :error="passwordErrors.confirmPassword"
        >
          <UInput
            v-model="passwordForm.confirmPassword"
            type="password"
            icon="i-heroicons-lock-closed"
          />
        </UFormGroup>

        <div class="flex gap-3 pt-4">
          <UButton
            type="submit"
            :label="$t('auth.change_password.submit')"
            :loading="changePasswordLoading"
            color="neutral"
          />
          <UButton
            :label="$t('auth.change_password.cancel')"
            variant="outline"
            @click="showChangePasswordModal = false"
          />
        </div>
      </form>
    </UModal>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue';
  import { useI18n } from 'vue-i18n';
  import { useAuth } from '~/composables/useAuth';
  import { useAuthSchemas } from '~/composables/useAuthSchemas';
  import { useAuthStore } from '~/stores/auth';

  definePageMeta({
    middleware: 'auth',
  });

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  const { t } = useI18n();
  const authStore = useAuthStore();
  const auth = useAuth();
  const { profileSchema, changePasswordSchema } = useAuthSchemas();

  const fileInput = ref<HTMLInputElement>();
  const showChangePasswordModal = ref(false);
  const avatarUrl = ref('');
  const error = ref('');
  const isLoading = ref(false);
  const changePasswordLoading = ref(false);
  const passwordStrength = ref(0);

  // Form data
  const form = reactive({
    firstName: '',
    lastName: '',
    phone: '',
    city: '',
    district: '',
    address: '',
    languagePreference: 'ar',
  });

  const initialForm = { ...form };

  const errors = reactive({
    firstName: '',
    lastName: '',
    phone: '',
    city: '',
    district: '',
    address: '',
    languagePreference: '',
  });

  const passwordForm = reactive({
    currentPassword: '',
    password: '',
    confirmPassword: '',
  });

  const passwordErrors = reactive({
    currentPassword: '',
    password: '',
    confirmPassword: '',
  });

  // Cities and districts (mock data)
  const cities = [
    { label: 'الرياض / Riyadh', value: 'riyadh' },
    { label: 'جدة / Jeddah', value: 'jeddah' },
  ];

  const districtsByCity: Record<string, Array<{ label: string; value: string }>> = {
    riyadh: [
      { label: 'الخليج / Al Khaleej', value: 'khaleej' },
      { label: 'العليا / Al Olaya', value: 'olaya' },
    ],
    jeddah: [{ label: 'البلد / Al Balad', value: 'balad' }],
  };

  const filteredDistricts = computed(() => {
    return districtsByCity[form.city] || [];
  });

  const isDirty = computed(() => {
    return JSON.stringify(form) !== JSON.stringify(initialForm);
  });

  const username = computed(() => `${form.firstName} ${form.lastName}`);

  // Load user data on mount
  onMounted(async () => {
    if (authStore.user) {
      form.firstName = authStore.user.firstName || '';
      form.lastName = authStore.user.lastName || '';
      form.phone = authStore.user.phone || '';
      form.city = authStore.user.city || '';
      form.district = authStore.user.district || '';
      form.address = authStore.user.address || '';
      form.languagePreference = authStore.user.languagePreference || 'ar';
      avatarUrl.value = authStore.user.avatarUrl || '';

      Object.assign(initialForm, form);
    }
  });

  const onCityChange = () => {
    form.district = '';
  };

  const triggerAvatarUpload = () => {
    fileInput.value?.click();
  };

  const onAvatarChange = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    error.value = '';

    // Validate MIME type (client-side safety check)
    const validMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!validMimeTypes.includes(file.type)) {
      error.value = `صيغة غير مدعومة: ${file.type} / Unsupported format: ${file.type}. يرجى استخدام JPEG, PNG أو WebP / Please use JPEG, PNG or WebP.`;
      return;
    }

    // Validate file size (5MB max)
    const maxSizeBytes = 5 * 1024 * 1024;
    if (file.size > maxSizeBytes) {
      const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
      error.value = `الصورة كبيرة جداً: ${sizeMB}MB / Image too large: ${sizeMB}MB. الحد الأقصى 5MB / Maximum 5MB.`;
      return;
    }

    // Validate dimensions (must be at least 400x400)
    try {
      const dimensions = await getImageDimensions(file);
      if (dimensions.width < 400 || dimensions.height < 400) {
        error.value = `الأبعاد قليلة جداً: ${dimensions.width}x${dimensions.height} / Dimensions too small: ${dimensions.width}x${dimensions.height}. الحد الأدنى 400x400.`;
        return;
      }
    } catch {
      error.value = 'فشل التحقق من الصورة / Failed to validate image';
      return;
    }

    // Preview: Load and display the image
    const reader = new FileReader();
    reader.onload = (e) => {
      avatarUrl.value = e.target?.result as string;
    };
    reader.onerror = () => {
      error.value = 'فشل قراءة الصورة / Failed to read image';
    };
    reader.readAsDataURL(file);
  };

  /**
   * Get image dimensions from file
   */
  const getImageDimensions = (file: File): Promise<{ width: number; height: number }> => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();

      reader.onload = (event) => {
        const img = new Image();
        img.onload = () => {
          resolve({ width: img.width, height: img.height });
        };
        img.onerror = () => {
          reject(new Error('Failed to load image'));
        };
        img.src = event.target?.result as string;
      };

      reader.onerror = () => {
        reject(new Error('Failed to read file'));
      };

      reader.readAsDataURL(file);
    });
  };

  const onCancel = () => {
    Object.assign(form, initialForm);
  };

  const onSubmit = async () => {
    error.value = '';

    try {
      profileSchema.parse(form);
      for (const key of Object.keys(errors)) {
        (errors as Record<string, string>)[key] = '';
      }
    } catch (e) {
      const err = e as { errors?: Array<{ path: string[]; message: string }> };
      for (const errItem of err.errors ?? []) {
        if (errItem.path[0]) {
          (errors as Record<string, string>)[errItem.path[0]] = errItem.message;
        }
      }
      return;
    }

    isLoading.value = true;

    try {
      // In a real app, call API to update profile
      await auth.updateProfile(form);
      Object.assign(initialForm, form);
    } catch (err) {
      const error = err as { response?: { data?: { error?: { message?: string } } } };
      error.value = error.response?.data?.error?.message || 'فشل تحديث الملف / Update failed';
    } finally {
      isLoading.value = false;
    }
  };

  const updatePasswordStrength = () => {
    const password = passwordForm.password;
    if (!password) {
      passwordStrength.value = 0;
      return;
    }

    let strength = 0;
    if (password.length >= 8) strength += 20;
    if (password.length >= 12) strength += 10;
    if (/[a-z]/.test(password)) strength += 15;
    if (/[A-Z]/.test(password)) strength += 15;
    if (/\d/.test(password)) strength += 15;
    if (/[!@#$%^&*]/.test(password)) strength += 25;

    passwordStrength.value = Math.min(strength, 100);
  };

  const openChangePasswordModal = () => {
    passwordForm.currentPassword = '';
    passwordForm.password = '';
    passwordForm.confirmPassword = '';
    for (const key of Object.keys(passwordErrors)) {
      (passwordErrors as Record<string, string>)[key] = '';
    }
    showChangePasswordModal.value = true;
  };

  const onChangePassword = async () => {
    try {
      changePasswordSchema.parse({
        currentPassword: passwordForm.currentPassword,
        password: passwordForm.password,
        confirmPassword: passwordForm.confirmPassword,
      });
      for (const key of Object.keys(passwordErrors)) {
        (passwordErrors as Record<string, string>)[key] = '';
      }
    } catch (e) {
      const err = e as { errors?: Array<{ path: string[]; message: string }> };
      for (const errItem of err.errors ?? []) {
        if (errItem.path[0]) {
          (passwordErrors as Record<string, string>)[errItem.path[0]] = errItem.message;
        }
      }
      return;
    }

    changePasswordLoading.value = true;

    try {
      await auth.changePassword(passwordForm.currentPassword, passwordForm.password);
      showChangePasswordModal.value = false;
    } catch (err) {
      const error = err as { response?: { data?: { error?: { message?: string } } } };
      (passwordErrors as Record<string, string>).currentPassword =
        error.response?.data?.error?.message || 'فشل التغيير / Failed';
    } finally {
      changePasswordLoading.value = false;
    }
  };

  const onLogout = async () => {
    await auth.logout();
  };
</script>
