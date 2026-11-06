<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

type Props = {
    value: number;
    color?:
        | 'indigo'
        | 'emerald'
        | 'amber'
        | 'rose'
        | 'sky'
        | 'violet'
        | 'slate';
    class?: string;
    barClass?: string;
};

const props = withDefaults(defineProps<Props>(), {
    color: 'indigo',
});

const colors: Record<NonNullable<Props['color']>, string> = {
    indigo: 'bg-indigo-500',
    emerald: 'bg-emerald-500',
    amber: 'bg-amber-500',
    rose: 'bg-rose-500',
    sky: 'bg-sky-500',
    violet: 'bg-violet-500',
    slate: 'bg-slate-500',
};

const width = computed(() => `${Math.min(100, Math.max(0, props.value))}%`);
</script>

<template>
    <div
        :class="
            cn(
                'h-1.5 w-full overflow-hidden rounded-full bg-muted',
                props.class,
            )
        "
    >
        <div
            :class="
                cn(
                    'h-full rounded-full transition-all duration-500',
                    colors[props.color],
                    props.barClass,
                )
            "
            :style="{ width }"
        />
    </div>
</template>
