<script setup lang="ts">
import { computed } from 'vue';
import { Terminal } from '@lucide/vue';
import { resolveTechOption } from '@/lib/techStack';
import { cn } from '@/lib/utils';

const props = defineProps<{
    name: string;
    class?: string;
}>();

const option = computed(() => resolveTechOption(props.name));
const iconClass = computed(() => option.value?.iconClass || null);
</script>

<template>
    <svg
        v-if="option?.customIcon === 'laravel-cloud'"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 16 16"
        fill="currentColor"
        :class="
            cn(
                'size-[1em] shrink-0 text-sm text-foreground',
                props.class,
            )
        "
        aria-hidden="true"
    >
        <path
            d="M15 10.33 10.33 15H1V5.67L5.67 1H15zM5.67 3.8v.93h5.6v5.6h.93V3.8zm0-.93h7.47v7.47h.93V1.93h-8.4v.93Zm0 2.8v4.67h4.67V5.67z"
        />
    </svg>
    <i
        v-else-if="iconClass"
        :class="
            cn(
                iconClass,
                'inline-flex shrink-0 items-center justify-center text-sm leading-none',
                props.class,
            )
        "
        aria-hidden="true"
    />
    <Terminal
        v-else
        :class="cn('size-3.5 shrink-0 text-muted-foreground', props.class)"
        aria-hidden="true"
    />
</template>
