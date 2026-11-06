<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    value: string | number;
    hint?: string;
    icon?: LucideIcon;
    tone?: 'neutral' | 'indigo' | 'emerald' | 'amber' | 'rose';
    class?: string;
};

const props = defineProps<Props>();

const toneClasses: Record<NonNullable<Props['tone']>, string> = {
    neutral: 'bg-muted text-foreground',
    indigo: 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-300',
    emerald: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300',
    amber: 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
    rose: 'bg-rose-500/10 text-rose-600 dark:text-rose-300',
};
</script>

<template>
    <div
        :class="
            cn(
                'relative overflow-hidden rounded-2xl border bg-card p-5 shadow-sm',
                $props.class,
            )
        "
    >
        <div class="flex items-start justify-between gap-3">
            <div>
                <p
                    class="text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                >
                    {{ label }}
                </p>
                <p class="mt-2 text-3xl font-semibold tracking-tight">
                    {{ value }}
                </p>
                <p v-if="hint" class="mt-1 text-xs text-muted-foreground">
                    {{ hint }}
                </p>
            </div>
            <div
                v-if="icon"
                :class="
                    cn(
                        'flex size-9 items-center justify-center rounded-xl',
                        toneClasses[tone ?? 'neutral'],
                    )
                "
            >
                <component :is="icon" class="size-4" />
            </div>
        </div>
        <slot />
    </div>
</template>
