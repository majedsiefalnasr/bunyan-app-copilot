<template>
  <AuthLayout>
    <AuthCard :title="currentStepTitle" :subtitle="currentStepSubtitle">
      <form class="space-y-6" @submit.prevent="onSubmit">
        <!-- Error Alert -->
        <UAlert
          v-if="error"
          :title="$t('errors.error')"
          color="red"
          icon="i-heroicons-exclamation-circle"
          :description="error"
          @close="error = null"
        />

        <!-- Step 1: Account Type Selection -->
        <div v-show="currentStep === 1" class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium text-[#171717] dark:text-white">
              {{ $t('auth.register.step_1_customer') }}
            </label>
            <div class="flex gap-3">
              <URadio v-model="form.userType" value="customer" :label="$t('roles.customer')" />
              <URadio v-model="form.userType" value="contractor" :label="$t('roles.contractor')" />
            </div>
          </div>
        </div>

        <!-- Step 2: Personal Information -->
        <div v-show="currentStep === 2" class="space-y-4">
          <UFormGroup
            :label="$t('auth.register.first_name')"
            name="firstName"
            :error="errors.firstName"
          >
            <UInput
              v-model="form.firstName"
              :placeholder="$t('auth.register.first_name_placeholder')"
            />
          </UFormGroup>

          <UFormGroup
            :label="$t('auth.register.last_name')"
            name="lastName"
            :error="errors.lastName"
          >
            <UInput
              v-model="form.lastName"
              :placeholder="$t('auth.register.last_name_placeholder')"
            />
          </UFormGroup>

          <UFormGroup :label="$t('auth.register.phone')" name="phone" :error="errors.phone">
            <UInput
              v-model="form.phone"
              type="tel"
              :placeholder="$t('auth.register.phone_placeholder')"
            />
          </UFormGroup>

          <UFormGroup
            :label="$t('auth.register.id_number')"
            name="idNumber"
            :error="errors.idNumber"
          >
            <UInput
              v-model="form.idNumber"
              :placeholder="$t('auth.register.id_number_placeholder')"
            />
          </UFormGroup>
        </div>

        <!-- Step 3: Address Information -->
        <div v-show="currentStep === 3" class="space-y-4">
          <UFormGroup :label="$t('auth.register.city')" name="city" :error="errors.city">
            <USelect
              v-model="form.city"
              :options="cities"
              :placeholder="$t('auth.register.city_placeholder')"
              searchable
              @update:model-value="onCityChange"
            />
          </UFormGroup>

          <UFormGroup
            :label="$t('auth.register.district')"
            name="district"
            :error="errors.district"
          >
            <USelect
              v-model="form.district"
              :options="filteredDistricts"
              :placeholder="$t('auth.register.district_placeholder')"
              searchable
              :disabled="!form.city"
            />
          </UFormGroup>

          <UFormGroup
            :label="$t('auth.register.address')"
            name="address"
            :error="errors.address"
            :help="`${form.address.length}/200`"
          >
            <UTextarea
              v-model="form.address"
              :placeholder="$t('auth.register.address_placeholder')"
              :rows="3"
              maxlength="200"
            />
          </UFormGroup>
        </div>

        <!-- Step 4: Email & Password -->
        <div v-show="currentStep === 4" class="space-y-4">
          <UFormGroup :label="$t('auth.register.email')" name="email" :error="errors.email">
            <UInput
              v-model="form.email"
              type="email"
              :placeholder="$t('auth.register.email_placeholder')"
              icon="i-heroicons-envelope"
            />
          </UFormGroup>

          <UFormGroup
            :label="$t('auth.register.password')"
            name="password"
            :error="errors.password"
          >
            <div class="flex gap-2">
              <UInput
                v-model="form.password"
                :type="passwordToggle.type"
                :placeholder="$t('auth.register.password_placeholder')"
                icon="i-heroicons-lock-closed"
                class="flex-1"
                @input="updatePasswordStrength"
              />
              <UButton
                :icon="passwordToggle.icon"
                color="gray"
                variant="ghost"
                :aria-label="passwordToggle.ariaLabel"
                @click="passwordToggle.toggle"
              />
            </div>

            <!-- Password Strength Indicator -->
            <PasswordStrength :score="passwordStrength" class="mt-2" />
          </UFormGroup>

          <UFormGroup
            :label="$t('auth.register.password_confirmation')"
            name="confirmPassword"
            :error="errors.confirmPassword"
          >
            <div class="flex gap-2">
              <UInput
                v-model="form.confirmPassword"
                :type="passwordToggle.type"
                :placeholder="$t('auth.register.password_confirmation_placeholder')"
                icon="i-heroicons-lock-closed"
                class="flex-1"
              />
              <UButton
                :icon="passwordToggle.icon"
                color="gray"
                variant="ghost"
                :aria-label="passwordToggle.ariaLabel"
                @click="passwordToggle.toggle"
              />
            </div>
          </UFormGroup>
        </div>

        <!-- Step Indicator -->
        <div class="flex justify-center gap-1 mt-6">
          <div
            v-for="step in 4"
            :key="step"
            :class="[
              'w-2 h-2 rounded-full transition-colors',
              currentStep === step
                ? 'bg-blue-600 dark:bg-blue-400'
                : 'bg-[rgba(0,0,0,0.12)] dark:bg-[rgba(255,255,255,0.12)]',
            ]"
          />
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-between gap-3 mt-6">
          <UButton
            v-if="currentStep > 1"
            :label="$t('auth.register.previous')"
            variant="outline"
            @click="previousStep"
          />

          <UButton
            v-if="currentStep < 4"
            :label="$t('auth.register.next')"
            color="neutral"
            class="ml-auto"
            @click="nextStep"
          />

          <UButton
            v-else
            type="submit"
            :label="$t('auth.register.submit')"
            color="neutral"
            :loading="isLoading"
            :disabled="isLoading"
            class="w-full"
          />
        </div>

        <!-- Sign In Link -->
        <div class="flex justify-center gap-1 text-sm mt-4">
          <span class="text-[#666666] dark:text-[#999999]">
            {{ $t('auth.register.has_account') }}
          </span>
          <NuxtLink
            :to="`/${locale}/auth/login`"
            class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
          >
            {{ $t('auth.register.login_link') }}
          </NuxtLink>
        </div>
      </form>
    </AuthCard>
  </AuthLayout>
</template>

<script setup lang="ts">
  import { computed, ref, shallowRef } from 'vue';
  import { useI18n } from 'vue-i18n';
  import { useRouter } from 'vue-router';
  import { useAuth } from '~/composables/useAuth';
  import { useAuthSchemas } from '~/composables/useAuthSchemas';
  import { usePasswordToggle } from '~/composables/usePasswordToggle';

  definePageMeta({
    middleware: 'guest',
  });

  const router = useRouter();
  const { locale } = useI18n();
  const auth = useAuth();
  const passwordToggle = usePasswordToggle();
  const { registerStep1Schema, registerStep2Schema, registerStep3Schema, registerStep4Schema } =
    useAuthSchemas();

  const currentStep = ref(1);
  const passwordStrength = ref(0);
  const isLoading = ref(false);
  const error = ref('');

  // Saudi cities (example data)
  const cities = [
    { label: 'الرياض / Riyadh', value: 'riyadh' },
    { label: 'جدة / Jeddah', value: 'jeddah' },
    { label: 'الدمام / Dammam', value: 'dammam' },
    { label: 'الكويت / Kuwait', value: 'kuwait' },
    { label: 'المدينة / Medina', value: 'medina' },
    { label: 'الإحساء / Ahsa', value: 'ahsa' },
  ];

  // Districts by city (example data)
  const districtsByCity: Record<string, Array<{ label: string; value: string }>> = {
    riyadh: [
      { label: 'الخليج / Al Khaleej', value: 'khaleej' },
      { label: 'العليا / Al Olaya', value: 'olaya' },
      { label: 'الروضة / Al Rowdah', value: 'rowdah' },
    ],
    jeddah: [
      { label: 'البلد / Al Balad', value: 'balad' },
      { label: 'الشاطئ / Al Shati', value: 'shati' },
      { label: 'الزاهر / Al Zaher', value: 'zaher' },
    ],
    dammam: [
      { label: 'الأزيزية / Al Aziziah', value: 'aziziah' },
      { label: 'الخبر / Al Khobar', value: 'khobar' },
    ],
    kuwait: [
      { label: 'حولي / Hawalli', value: 'hawalli' },
      { label: 'الميدان / Al Meidan', value: 'meidan' },
    ],
    medina: [{ label: 'العنابس / Al Enabis', value: 'enabis' }],
    ahsa: [{ label: 'المبرز / Al Mubarraz', value: 'mubarraz' }],
  };

  // Use shallow reactive to prevent cascading updates on nested fields
  const form = shallowRef({
    userType: 'customer',
    firstName: '',
    lastName: '',
    phone: '',
    idNumber: '',
    city: '',
    district: '',
    address: '',
    email: '',
    password: '',
    confirmPassword: '',
  });

  const errors = shallowRef({
    userType: '',
    firstName: '',
    lastName: '',
    phone: '',
    idNumber: '',
    city: '',
    district: '',
    address: '',
    email: '',
    password: '',
    confirmPassword: '',
  });

  const currentStepTitle = computed(() => {
    const titles = [
      $t('auth.register.step_1_title'),
      $t('auth.register.step_2_title'),
      $t('auth.register.step_3_title'),
      $t('auth.register.step_4_title'),
    ];
    return titles[currentStep.value - 1];
  });

  const currentStepSubtitle = computed(() => {
    const subtitles = [
      $t('auth.register.step_1_subtitle'),
      $t('auth.register.step_2_subtitle'),
      $t('auth.register.step_3_subtitle'),
      $t('auth.register.step_4_subtitle'),
    ];
    return subtitles[currentStep.value - 1];
  });

  const filteredDistricts = computed(() => {
    if (!form.value.city) return [];
    return districtsByCity[form.value.city] || [];
  });

  const updateForm = (updates: Partial<typeof form.value>) => {
    form.value = { ...form.value, ...updates };
  };

  const updateErrors = (updates: Partial<typeof errors.value>) => {
    errors.value = { ...errors.value, ...updates };
  };

  const onCityChange = () => {
    updateForm({ district: '' });
  };

  const updatePasswordStrength = () => {
    if (!form.value.password) {
      passwordStrength.value = 0;
      return;
    }

    let strength = 0;

    // Length check
    if (form.value.password.length >= 8) strength += 20;
    if (form.value.password.length >= 12) strength += 10;

    // Character variety checks
    if (/[a-z]/.test(form.value.password)) strength += 15;
    if (/[A-Z]/.test(form.value.password)) strength += 15;
    if (/\d/.test(form.value.password)) strength += 15;
    if (/[!@#$%^&*]/.test(form.value.password)) strength += 25;

    passwordStrength.value = Math.min(strength, 100);
  };

  const validateStep = async (): Promise<boolean> => {
    // Clear errors for current step
    try {
      if (currentStep.value === 1) {
        registerStep1Schema.parse(form.value);
        updateErrors({ userType: '' });
      } else if (currentStep.value === 2) {
        registerStep2Schema.parse({
          firstName: form.value.firstName,
          lastName: form.value.lastName,
          phone: form.value.phone,
          idNumber: form.value.idNumber,
        });
        updateErrors({ firstName: '', lastName: '', phone: '', idNumber: '' });
      } else if (currentStep.value === 3) {
        registerStep3Schema.parse({
          city: form.value.city,
          district: form.value.district,
          address: form.value.address,
        });
        updateErrors({ city: '', district: '', address: '' });
      } else if (currentStep.value === 4) {
        registerStep4Schema.parse({
          email: form.value.email,
          password: form.value.password,
          confirmPassword: form.value.confirmPassword,
        });
        updateErrors({ email: '', password: '', confirmPassword: '' });
      }
      return true;
    } catch (e) {
      const err = e as { errors?: Array<{ path: string[]; message: string }> };
      for (const errItem of err.errors ?? []) {
        const field = errItem.path[0];
        if (field) {
          updateErrors({ [field]: errItem.message });
        }
      }
      return false;
    }
  };

  const nextStep = async () => {
    const isValid = await validateStep();
    if (isValid && currentStep.value < 4) {
      currentStep.value++;
    }
  };

  const previousStep = () => {
    if (currentStep.value > 1) {
      currentStep.value--;
    }
  };

  const onSubmit = async () => {
    error.value = '';

    const isValid = await validateStep();
    if (!isValid) {
      return;
    }

    isLoading.value = true;

    try {
      await auth.register({
        role: form.value.userType,
        firstName: form.value.firstName,
        lastName: form.value.lastName,
        phone: form.value.phone,
        idNumber: form.value.idNumber,
        city: form.value.city,
        district: form.value.district,
        address: form.value.address,
        email: form.value.email,
        password: form.value.password,
        password_confirmation: form.value.confirmPassword,
      });

      // Redirect to email verification page
      await router.push(
        `/${locale.value}/auth/verify-email?email=${encodeURIComponent(form.value.email)}`
      );
    } catch (err) {
      const error = err as { response?: { data?: { error?: { message?: string } } } };
      const errorMessage =
        error.response?.data?.error?.message || 'فشل إنشاء الحساب / Registration failed';
      error.value = errorMessage;
    } finally {
      isLoading.value = false;
    }
  };
</script>
