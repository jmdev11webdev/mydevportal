<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 42"
        fill="none"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M20 2.6 36.4 9.4v10.7c0 8.4-6.6 15.4-16.4 18.3C10.2 35.5 3.6 28.5 3.6 20.1V9.4L20 2.6Z"
            stroke="currentColor"
            stroke-width="2.4"
            stroke-linejoin="round"
        />
        <path
            d="m13.6 15.5 5 5-5 5M21.6 25.5h6"
            stroke="currentColor"
            stroke-width="2.4"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>
