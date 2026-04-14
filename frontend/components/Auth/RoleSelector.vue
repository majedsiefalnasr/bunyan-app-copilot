<template>
  <fieldset class="space-y-3">
    <legend class="text-sm font-medium text-[#171717] dark:text-white mb-3">
      {{ label }}
    </legend>
    <div class="flex gap-3">
      <label
        v-for="option in options"
        :key="option.value"
        class="flex items-center gap-2 cursor-pointer"
      >
        <URadio v-model="selectedValue" :value="option.value" :disabled="disabled" />
        <span class="text-sm text-[#666666] dark:text-[#999999]">
          {{ option.label }}
        </span>
      </label>
    </div>
  </fieldset>
</template>

<script setup lang="ts">
  import { computed } from 'vue';

  interface Option {
    value: string;
    label: string;
  }

  interface Props {
    modelValue: string;
    label?: string;
    options: Option[];
    disabled?: boolean;
  }

  interface Emits {
    (e: 'update:modelValue', value: string): void;
  }

  const props = withDefaults(defineProps<Props>(), {
    label: '',
    disabled: false,
  });

  const emit = defineEmits<Emits>();

  const selectedValue = computed({
    get: () => props.modelValue,
    set: (value: string) => emit('update:modelValue', value),
  });
</script>
