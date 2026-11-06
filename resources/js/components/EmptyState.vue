<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

type Props = {
    icon?: LucideIcon;
    title: string;
    description?: string;
    class?: string;
};

defineProps<Props>();
</script>

<template>
    <div
        :class="[
            'flex flex-col items-center justify-center rounded-2xl border border-dashed bg-muted/30 px-6 py-12 text-center',
            $props.class,
        ]"
    >
        <div
            v-if="icon"
            class="mb-3 flex size-11 items-center justify-center rounded-full border bg-background text-muted-foreground shadow-sm"
        >
            <component :is="icon" class="size-5" />
        </div>
        <p class="text-sm font-semibold">{{ title }}</p>
        <p
            v-if="description"
            class="mt-1 max-w-sm text-xs text-muted-foreground"
        >
            {{ description }}
        </p>
        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>
    </div>
</template>
