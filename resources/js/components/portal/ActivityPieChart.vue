<script setup lang="ts">
import { computed, ref } from 'vue';

export type PieSlice = {
    label: string;
    value: number;
    color: string;
};

const props = withDefaults(
    defineProps<{
        slices: PieSlice[];
        centerLabel?: string;
        size?: number;
    }>(),
    {
        centerLabel: 'Total',
        size: 132,
    },
);

const hoveredIndex = ref<number | null>(null);

const total = computed(() =>
    props.slices.reduce((sum, slice) => sum + Math.max(0, slice.value), 0),
);

const radius = 40;
const circumference = 2 * Math.PI * radius;

const segments = computed(() => {
    if (total.value === 0) {
        return [];
    }

    let offset = 0;

    return props.slices.map((slice, index) => {
        const value = Math.max(0, slice.value);
        const fraction = value / total.value;
        const length = fraction * circumference;
        const segment = {
            ...slice,
            index,
            percent: Math.round(fraction * 100),
            dashArray: `${length} ${circumference - length}`,
            dashOffset: -offset,
        };

        offset += length;

        return segment;
    });
});

const activeSegment = computed(() =>
    hoveredIndex.value !== null
        ? (segments.value[hoveredIndex.value] ?? null)
        : null,
);
</script>

<template>
    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-center">
        <div
            class="relative shrink-0"
            :style="{ width: `${size}px`, height: `${size}px` }"
        >
            <svg
                viewBox="0 0 100 100"
                class="size-full -rotate-90 overflow-visible"
                role="img"
                aria-label="Overall workspace activity distribution"
            >
                <circle
                    cx="50"
                    cy="50"
                    :r="radius"
                    fill="transparent"
                    stroke="currentColor"
                    stroke-width="12"
                    class="text-muted/40"
                />

                <circle
                    v-for="segment in segments"
                    :key="segment.label"
                    cx="50"
                    cy="50"
                    :r="radius"
                    fill="transparent"
                    :stroke="segment.color"
                    :stroke-width="hoveredIndex === segment.index ? 15 : 12"
                    :stroke-dasharray="segment.dashArray"
                    :stroke-dashoffset="segment.dashOffset"
                    stroke-linecap="butt"
                    class="cursor-pointer transition-all duration-200"
                    :class="
                        hoveredIndex !== null && hoveredIndex !== segment.index
                            ? 'opacity-40'
                            : 'opacity-100'
                    "
                    @mouseenter="hoveredIndex = segment.index"
                    @mouseleave="hoveredIndex = null"
                />
            </svg>

            <div
                class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center"
            >
                <span class="text-lg leading-none font-semibold tabular-nums">
                    {{ activeSegment ? `${activeSegment.percent}%` : total }}
                </span>
                <span
                    class="mt-1 max-w-[74px] truncate text-[10px] text-muted-foreground"
                >
                    {{ activeSegment ? activeSegment.label : centerLabel }}
                </span>
            </div>
        </div>

        <ul class="w-full min-w-0 flex-1 space-y-1.5 text-xs">
            <li
                v-for="(slice, index) in slices"
                :key="slice.label"
                class="flex cursor-pointer items-center justify-between gap-2 rounded-md px-1.5 py-0.5 transition-colors"
                :class="
                    hoveredIndex === index ? 'bg-muted/60' : 'hover:bg-muted/40'
                "
                @mouseenter="hoveredIndex = index"
                @mouseleave="hoveredIndex = null"
            >
                <span class="flex min-w-0 items-center gap-2">
                    <span
                        class="size-2 shrink-0 rounded-full"
                        :style="{ backgroundColor: slice.color }"
                    />
                    <span class="truncate text-muted-foreground">
                        {{ slice.label }}
                    </span>
                </span>
                <span
                    class="shrink-0 font-mono text-[11px] font-medium tabular-nums"
                >
                    {{ slice.value }}
                </span>
            </li>
        </ul>
    </div>
</template>
