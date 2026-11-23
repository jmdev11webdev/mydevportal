<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import EyeCareHeaderWidget from '@/components/EyeCareHeaderWidget.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 bg-background/90 px-3 backdrop-blur-md transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 sm:h-16 sm:px-4 md:px-6"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1 shrink-0" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" class="min-w-0" />
            </template>
        </div>
        <EyeCareHeaderWidget class="shrink-0" />
    </header>
</template>
