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
    <img
        src="/icon.png"
        alt="MyDevPortal"
        :class="['rounded-lg object-cover', className]"
        v-bind="$attrs"
    />
</template>
