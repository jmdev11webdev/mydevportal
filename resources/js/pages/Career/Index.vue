<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    Award,
    CalendarClock,
    MoreHorizontal,
    Pencil,
    Plus,
    Send,
    Target,
    Trash2,
    Trophy,
} from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import CareerDialog from '@/components/portal/CareerDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    careerStatusClasses,
    careerStatusLabel,
    careerTypeClasses,
    careerTypeLabel,
} from '@/lib/colors';
import { formatDate, relativeDate } from '@/lib/format';
import type { CareerEntry, CareerType } from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Career', href: '/career' }],
    },
});

const props = defineProps<{
    entries: CareerEntry[];
    summary: {
        active_goals: number;
        applications: number;
        achievements: number;
        certifications: number;
    };
}>();

const dialogOpen = ref(false);
const editing = ref<CareerEntry | null>(null);
const defaultType = ref<CareerType>('goal');

const goals = computed(() =>
    props.entries.filter((entry) => entry.type === 'goal'),
);
const applications = computed(() =>
    props.entries.filter((entry) => entry.type === 'application'),
);
const wins = computed(() =>
    props.entries.filter(
        (entry) =>
            entry.type === 'achievement' || entry.type === 'certification',
    ),
);

const columns = computed(() => [
    {
        key: 'goal',
        title: 'Goals in motion',
        description: 'The direction you are deliberately moving in.',
        icon: Target,
        entries: goals.value,
        tone: 'text-indigo-600 dark:text-indigo-300',
        soft: 'bg-indigo-500/10',
        type: 'goal' as CareerType,
    },
    {
        key: 'application',
        title: 'Applications',
        description: 'Roles and conversations worth tracking.',
        icon: Send,
        entries: applications.value,
        tone: 'text-sky-600 dark:text-sky-300',
        soft: 'bg-sky-500/10',
        type: 'application' as CareerType,
    },
    {
        key: 'win',
        title: 'Wins & credentials',
        description: 'Evidence of the work you have already done.',
        icon: Trophy,
        entries: wins.value,
        tone: 'text-amber-600 dark:text-amber-300',
        soft: 'bg-amber-500/10',
        type: 'achievement' as CareerType,
    },
]);

const summaryCards = computed(() => [
    { label: 'Active goals', value: props.summary.active_goals },
    { label: 'Applications', value: props.summary.applications },
    { label: 'Achievements', value: props.summary.achievements },
    { label: 'Certifications', value: props.summary.certifications },
]);

function createEntry(type: CareerType = 'goal'): void {
    editing.value = null;
    defaultType.value = type;
    dialogOpen.value = true;
}

function editEntry(entry: CareerEntry): void {
    editing.value = entry;
    dialogOpen.value = true;
}

function removeEntry(entry: CareerEntry): void {
    if (!window.confirm(`Delete "${entry.title}"?`)) {
        return;
    }

    router.delete(`/career/${entry.id}`, { preserveScroll: true });
}

function entryIcon(entry: CareerEntry) {
    if (entry.type === 'goal') {
        return Target;
    }

    if (entry.type === 'application') {
        return Send;
    }

    if (entry.type === 'certification') {
        return Award;
    }

    return Trophy;
}
</script>

<template>
    <Head title="Career" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Private workspace"
            title="Career"
            description="A quiet record of where you're going, what you've applied for, and what you've already proven."
            :icon="Target"
        >
            <template #actions>
                <Button @click="createEntry()">
                    <Plus class="size-4" />
                    New entry
                </Button>
            </template>
        </PageHeader>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div
                v-for="card in summaryCards"
                :key="card.label"
                class="rounded-2xl border bg-card px-5 py-4 shadow-sm"
            >
                <p
                    class="text-[11px] tracking-[0.14em] text-muted-foreground uppercase"
                >
                    {{ card.label }}
                </p>
                <p class="mt-1 text-2xl font-semibold tracking-tight">
                    {{ card.value }}
                </p>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <section
                v-for="column in columns"
                :key="column.key"
                class="flex flex-col rounded-2xl border bg-muted/20 p-3"
            >
                <header
                    class="flex items-start justify-between gap-3 px-1 py-2"
                >
                    <div class="flex items-start gap-2.5">
                        <div
                            :class="[
                                'flex size-8 items-center justify-center rounded-lg',
                                column.soft,
                                column.tone,
                            ]"
                        >
                            <component :is="column.icon" class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold">
                                {{ column.title }}
                            </h2>
                            <p class="mt-0.5 text-[11px] text-muted-foreground">
                                {{ column.description }}
                            </p>
                        </div>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="text-muted-foreground"
                        :aria-label="`Add ${column.title}`"
                        @click="createEntry(column.type)"
                    >
                        <Plus class="size-4" />
                    </Button>
                </header>

                <div class="flex flex-col gap-2">
                    <article
                        v-for="entry in column.entries"
                        :key="entry.id"
                        class="group rounded-xl border bg-card p-4 shadow-sm transition hover:border-foreground/20 hover:shadow"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-start gap-2.5">
                                <div
                                    :class="[
                                        'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg',
                                        entry.type === 'goal'
                                            ? 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-300'
                                            : entry.type === 'application'
                                              ? 'bg-sky-500/10 text-sky-600 dark:text-sky-300'
                                              : 'bg-amber-500/10 text-amber-600 dark:text-amber-300',
                                    ]"
                                >
                                    <component
                                        :is="entryIcon(entry)"
                                        class="size-4"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="leading-snug font-semibold">
                                        {{ entry.title }}
                                    </h3>
                                    <p
                                        v-if="entry.organization"
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ entry.organization }}
                                    </p>
                                </div>
                            </div>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="-mt-1 -mr-2 text-muted-foreground transition focus-visible:opacity-100 data-[state=open]:opacity-100 sm:opacity-0 sm:group-hover:opacity-100"
                                        :aria-label="`Actions for ${entry.title}`"
                                    >
                                        <MoreHorizontal class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-40">
                                    <DropdownMenuItem @click="editEntry(entry)">
                                        <Pencil class="size-4" />
                                        Edit entry
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        variant="destructive"
                                        @click="removeEntry(entry)"
                                    >
                                        <Trash2 class="size-4" />
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>

                        <p
                            v-if="entry.description"
                            class="mt-3 line-clamp-3 text-xs leading-relaxed text-muted-foreground"
                        >
                            {{ entry.description }}
                        </p>

                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                            <Badge
                                variant="outline"
                                :class="careerTypeClasses[entry.type]"
                            >
                                {{ careerTypeLabel[entry.type] }}
                            </Badge>
                            <Badge
                                variant="outline"
                                :class="careerStatusClasses[entry.status]"
                            >
                                {{ careerStatusLabel[entry.status] }}
                            </Badge>
                        </div>

                        <div
                            v-if="entry.target_date || entry.completed_at"
                            class="mt-3 flex items-center gap-1.5 border-t pt-3 text-[11px] text-muted-foreground"
                        >
                            <CalendarClock class="size-3.5" />
                            <template v-if="entry.completed_at">
                                Achieved {{ formatDate(entry.completed_at) }}
                            </template>
                            <template v-else-if="entry.target_date">
                                Target {{ relativeDate(entry.target_date) }} ·
                                {{ formatDate(entry.target_date) }}
                            </template>
                        </div>
                    </article>

                    <button
                        v-if="!column.entries.length"
                        type="button"
                        class="flex items-center justify-center gap-1.5 rounded-xl border border-dashed py-8 text-xs text-muted-foreground transition hover:border-foreground/30 hover:text-foreground"
                        @click="createEntry(column.type)"
                    >
                        <Plus class="size-3.5" />
                        Add {{ column.key === 'win' ? 'win' : column.type }}
                    </button>
                </div>
            </section>
        </div>
    </div>

    <CareerDialog
        v-model:open="dialogOpen"
        :entry="editing"
        :default-type="defaultType"
    />
</template>
