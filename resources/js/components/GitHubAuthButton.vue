<script setup lang="ts">
import { computed } from 'vue';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        href?: string;
        label?: string;
    }>(),
    {
        href: '/auth/github/redirect',
        label: 'Continue with GitHub',
    },
);

const classes = computed(() =>
    cn(buttonVariants({ variant: 'outline', size: 'lg' }), 'w-full gap-2'),
);
</script>

<template>
    <a :href="props.href" :class="classes" data-test="github-login-button">
        <svg
            aria-hidden="true"
            viewBox="0 0 24 24"
            fill="currentColor"
            class="size-4"
        >
            <path
                d="M12 .5C5.73.5.5 5.73.5 12a11.5 11.5 0 0 0 7.86 10.92c.58.1.79-.25.79-.56v-2.17c-3.2.69-3.88-1.36-3.88-1.36-.52-1.33-1.28-1.68-1.28-1.68-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.03 1.75 2.69 1.25 3.34.95.1-.74.4-1.25.73-1.54-2.55-.29-5.24-1.28-5.24-5.68 0-1.26.45-2.29 1.18-3.1-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.18a10.9 10.9 0 0 1 5.74 0c2.19-1.49 3.15-1.18 3.15-1.18.62 1.58.23 2.75.11 3.04.74.81 1.18 1.84 1.18 3.1 0 4.42-2.69 5.39-5.25 5.67.41.36.78 1.06.78 2.14v3.17c0 .31.21.67.8.56A11.5 11.5 0 0 0 23.5 12C23.5 5.73 18.27.5 12 .5Z"
            />
        </svg>
        {{ props.label }}
    </a>
</template>
