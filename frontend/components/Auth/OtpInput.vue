<template>
  <div class="flex gap-2 justify-between">
    <input
      v-for="(digit, index) in digits"
      :key="index"
      v-model="digits[index]"
      type="text"
      inputmode="numeric"
      maxlength="1"
      pattern="[0-9]"
      class="w-10 h-10 text-center border border-[rgba(0,0,0,0.12)] dark:border-[rgba(255,255,255,0.12)] rounded-md font-semibold text-lg"
      @input="handleInput(index, $event)"
      @keydown="handleKeyDown(index, $event)"
      @paste="handlePaste"
    />
  </div>
</template>

<script setup lang="ts">
  import { ref, watch } from 'vue';

  type Emits = ((e: 'complete', code: string) => void) &
    ((e: 'update:modelValue', value: string) => void);

  interface Props {
    modelValue: string;
  }

  const props = defineProps<Props>();
  const emit = defineEmits<Emits>();

  const digits = ref<string[]>(['', '', '', '', '', '']);

  // Update digits when modelValue changes
  watch(
    () => props.modelValue,
    (newValue) => {
      for (let i = 0; i < 6; i++) {
        digits.value[i] = newValue[i] || '';
      }
    },
    { immediate: true }
  );

  // Emit complete code when all 6 digits are filled
  watch(
    digits,
    (newDigits) => {
      const code = newDigits.join('');
      emit('update:modelValue', code);
      if (code.length === 6) {
        emit('complete', code);
      }
    },
    { deep: true }
  );

  const handleInput = (index: number, event: Event) => {
    const target = event.target as HTMLInputElement;
    const value = target.value;

    // Only allow digits
    if (!/^\d?$/.test(value)) {
      digits.value[index] = '';
      return;
    }

    digits.value[index] = value;

    // Move to next input if value is entered
    if (value && index < 5) {
      const nextInput = document.querySelectorAll('input')[index + 1] as HTMLInputElement;
      if (nextInput) {
        nextInput.focus();
      }
    }
  };

  const handleKeyDown = (index: number, event: KeyboardEvent) => {
    if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
      // Move to previous input on backspace
      const prevInput = document.querySelectorAll('input')[index - 1] as HTMLInputElement;
      if (prevInput) {
        prevInput.focus();
        prevInput.value = '';
        digits.value[index - 1] = '';
      }
    }

    // Allow arrow keys
    if (event.key === 'ArrowRight' && index < 5) {
      const nextInput = document.querySelectorAll('input')[index + 1] as HTMLInputElement;
      if (nextInput) {
        nextInput.focus();
      }
    }

    if (event.key === 'ArrowLeft' && index > 0) {
      const prevInput = document.querySelectorAll('input')[index - 1] as HTMLInputElement;
      if (prevInput) {
        prevInput.focus();
      }
    }
  };

  const handlePaste = (event: ClipboardEvent) => {
    event.preventDefault();
    const clipboardData =
      event.clipboardData ||
      (window as unknown as { clipboardData: DataTransfer | null }).clipboardData;
    const pastedText = clipboardData?.getData('text') ?? '';
    const digitOnly = pastedText.replace(/\D/g, '').slice(0, 6);

    for (let i = 0; i < 6; i++) {
      digits.value[i] = digitOnly[i] || '';
    }

    // Focus on last filled input or last input if all filled
    const lastFilledIndex = Math.min(digitOnly.length, 5);
    const targetInput = document.querySelectorAll('input')[lastFilledIndex] as HTMLInputElement;
    if (targetInput) {
      targetInput.focus();
    }
  };
</script>
