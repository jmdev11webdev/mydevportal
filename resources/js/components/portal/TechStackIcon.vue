<script setup lang="ts">
import { computed } from 'vue';
import { Terminal } from '@lucide/vue';
import { getTechIconClass } from '@/lib/techStack';
import { cn } from '@/lib/utils';

const props = defineProps<{
    name: string;
    class?: string;
}>();

const iconClass = computed(() => getTechIconClass(props.name));
</script>

<template>
    <i
        v-if="iconClass"
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
