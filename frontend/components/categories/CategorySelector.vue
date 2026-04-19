<script setup lang="ts">
  import type { Category } from '~/types';

  interface Props {
    modelValue?: number | null;
    categories: Category[];
    placeholder?: string;
    disabled?: boolean;
    excludeId?: number | null; // Exclude this category from selection (useful for moving)
  }

  interface Emits {
    (e: 'update:modelValue', value: number | null): void;
  }

  const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    placeholder: 'categories.selectCategory',
    disabled: false,
    excludeId: null,
  });

  defineEmits<Emits>();

  interface FlatOption {
    value: number;
    label: string;
    indent: number;
  }

  /**
   * Flatten tree structure into selectable options with indentation display
   */
  const flattenedOptions = computed(() => {
    const options: FlatOption[] = [];

    const flatten = (items: Category[], level: number = 0) => {
      items.forEach((item) => {
        // Skip excluded category
        if (props.excludeId && item.id === props.excludeId) {
          return;
        }

        options.push({
          value: item.id,
          label: `${'  '.repeat(level)}${item.name_ar}`,
          indent: level,
        });

        if (item.children && item.children.length > 0) {
          flatten(item.children, level + 1);
        }
      });
    };

    flatten(props.categories);
    return options;
  });

  // Add null option for "no parent"
  const { t } = useI18n();

  // Add null option for "no parent" (label translated)
  const selectOptions = computed(() => [
    { value: null, label: t('categories.noParent') } as { value: null | number; label: string },
    ...flattenedOptions.value,
  ]);
</script>

<template>
  <USelectMenu
    :model-value="modelValue"
    :options="selectOptions"
    option-attribute="label"
    value-attribute="value"
    :placeholder="$t(placeholder)"
    :disabled="disabled"
    searchable
    nullable
    @update:model-value="$emit('update:modelValue', ($event as any)?.value ?? null)"
  />
</template>
