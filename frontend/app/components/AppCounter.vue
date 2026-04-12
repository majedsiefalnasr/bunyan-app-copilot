<template>
    <div>
        <h3>Counter: {{ count }}</h3>
        <button @click="increment">Increment</button>
        <button @click="decrement">Decrement</button>
    </div>
</template>

<script setup lang="ts">
    import { ref } from 'vue';

    const count = ref<number>(0);

    const increment = () => {
        count.value++;
    };

    const decrement = () => {
        count.value--;
    };
</script>

<style scoped>
    button {
        margin: 5px;
    }
</style>
