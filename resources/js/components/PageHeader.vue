<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';

type Props = {
    title: string;
    description?: string;
    eyebrow?: string;
    icon?: LucideIcon;
    class?: string;
};

defineProps<Props>();
</script>

<template>
    <div
        :class="
            cn('flex flex-wrap items-end justify-between gap-4', $props.class)
        "
    >
        <div class="flex min-w-0 flex-1 items-start gap-3 sm:gap-4">
            <div
                v-if="icon"
                class="hidden size-11 shrink-0 items-center justify-center rounded-2xl border bg-gradient-to-br from-muted to-background text-foreground shadow-sm sm:flex"
            >
                <component :is="icon" class="size-5" />
            </div>
            <div class="min-w-0 flex-1">
                <p
                    v-if="eyebrow"
                    class="mb-1 text-[11px] font-semibold tracking-[0.18em] text-muted-foreground uppercase"
                >
                    {{ eyebrow }}
                </p>
                <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">
                    {{ title }}
                </h1>
                <p
                    v-if="description"
                    class="mt-1 max-w-2xl text-sm text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
        </div>
        <div
            class="flex w-full flex-wrap items-center gap-2 sm:w-auto sm:justify-end"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
