<script setup lang="ts">
import { computed } from 'vue';
import type { LucideIcon } from '@lucide/vue';
import { cn } from '@/lib/utils';

type Tone = 'neutral' | 'indigo' | 'emerald' | 'amber' | 'rose' | 'sky';

type Props = {
    label: string;
    value: string | number;
    hint?: string;
    icon?: LucideIcon;
    tone?: Tone;
    accent?: Tone;
    class?: string;
};

const props = defineProps<Props>();

const activeTone = computed<Tone>(
    () => props.tone ?? props.accent ?? 'neutral',
);

const iconToneClasses: Record<Tone, string> = {
    neutral:
        'border-border/80 bg-gradient-to-br from-muted to-background text-foreground',
    indigo: 'border-indigo-500/25 bg-gradient-to-br from-indigo-500/18 to-indigo-500/5 text-indigo-600 dark:text-indigo-300',
    emerald:
        'border-emerald-500/25 bg-gradient-to-br from-emerald-500/18 to-emerald-500/5 text-emerald-600 dark:text-emerald-300',
    amber: 'border-amber-500/25 bg-gradient-to-br from-amber-500/18 to-amber-500/5 text-amber-600 dark:text-amber-300',
    rose: 'border-rose-500/25 bg-gradient-to-br from-rose-500/18 to-rose-500/5 text-rose-600 dark:text-rose-300',
    sky: 'border-sky-500/25 bg-gradient-to-br from-sky-500/18 to-sky-500/5 text-sky-600 dark:text-sky-300',
};

const accentBarClasses: Record<Tone, string> = {
    neutral: 'from-transparent via-foreground/15 to-transparent',
    indigo: 'from-transparent via-indigo-500/50 to-transparent',
    emerald: 'from-transparent via-emerald-500/50 to-transparent',
    amber: 'from-transparent via-amber-500/50 to-transparent',
    rose: 'from-transparent via-rose-500/50 to-transparent',
    sky: 'from-transparent via-sky-500/50 to-transparent',
};
</script>

<template>
    <div
        :class="
            cn(
                'group relative overflow-hidden rounded-2xl border border-border/80 bg-card p-4 shadow-xs transition-all duration-200 hover:border-foreground/15 hover:shadow-md sm:p-5',
                $props.class,
            )
        "
    >
        <div
            :class="
                cn(
                    'pointer-events-none absolute inset-x-6 top-0 h-px bg-gradient-to-r opacity-70 transition-opacity group-hover:opacity-100',
                    accentBarClasses[activeTone],
                )
            "
        />
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p
                    class="text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                >
                    {{ label }}
                </p>
                <p
                    class="mt-2 text-2xl font-semibold tracking-tight tabular-nums sm:text-3xl"
                >
                    {{ value }}
                </p>
                <p
                    v-if="hint"
                    class="mt-1 truncate text-xs text-muted-foreground"
                >
                    {{ hint }}
                </p>
            </div>
            <div
                v-if="icon"
                :class="
                    cn(
                        'flex size-10 shrink-0 items-center justify-center rounded-xl border shadow-xs ring-1 ring-black/5 ring-inset dark:ring-white/5',
                        iconToneClasses[activeTone],
                    )
                "
            >
                <component
                    :is="icon"
                    class="size-[18px]"
                    :stroke-width="1.85"
                />
            </div>
        </div>
        <slot />
    </div>
</template>
