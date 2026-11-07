<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    BookOpen,
    BookMarked,
    CheckCircle2,
    FileText,
    GraduationCap,
    MoreHorizontal,
    Pencil,
    PlayCircle,
    Plus,
    RotateCcw,
    Search,
    Trash2,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import LearningDialog from '@/components/portal/LearningDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { learningStatusClasses, learningStatusLabel } from '@/lib/colors';
import type {
    LearningFormat,
    LearningResource,
    LearningStatus,
} from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Learning', href: '/learning' }],
    },
});

const props = defineProps<{
    resources: LearningResource[];
    summary: {
        total: number;
        in_progress: number;
        completed: number;
        average_progress: number;
    };
}>();

const dialogOpen = ref(false);
const editing = ref<LearningResource | null>(null);
const filter = ref<'all' | LearningStatus>('all');
const search = ref('');

const filters: { label: string; value: 'all' | LearningStatus }[] = [
    { label: 'All', value: 'all' },
    { label: 'In progress', value: 'in_progress' },
    { label: 'Planned', value: 'planned' },
    { label: 'Completed', value: 'completed' },
];

const formatIcons: Record<LearningFormat, typeof BookOpen> = {
    course: GraduationCap,
    book: BookOpen,
    article: FileText,
    video: PlayCircle,
    docs: BookMarked,
};

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.resources.filter((resource) => {
        const matchesFilter =
            filter.value === 'all' || resource.status === filter.value;
        const matchesSearch =
            !term ||
            resource.title.toLowerCase().includes(term) ||
            (resource.provider ?? '').toLowerCase().includes(term) ||
            resource.category.toLowerCase().includes(term);

        return matchesFilter && matchesSearch;
    });
});

function createResource(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function editResource(resource: LearningResource): void {
    editing.value = resource;
    dialogOpen.value = true;
}

function setProgress(resource: LearningResource, progress: number): void {
    router.patch(
        `/learning/${resource.id}/progress`,
        { progress: Math.min(100, Math.max(0, progress)) },
        { preserveScroll: true },
    );
}

function removeResource(resource: LearningResource): void {
    if (
        !window.confirm(`Remove "${resource.title}" from your learning list?`)
    ) {
        return;
    }

    router.delete(`/learning/${resource.id}`, { preserveScroll: true });
}

function filterCount(value: 'all' | LearningStatus): number {
    if (value === 'all') {
        return props.resources.length;
    }

    return props.resources.filter((resource) => resource.status === value)
        .length;
}

const summaryCards = computed(() => [
    { label: 'Resources', value: props.summary.total },
    { label: 'In progress', value: props.summary.in_progress },
    { label: 'Completed', value: props.summary.completed },
    { label: 'Avg. progress', value: `${props.summary.average_progress}%` },
]);
</script>

<template>
    <Head title="Learning" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Grow"
            title="Learning"
            description="Your personal curriculum. Track what you're reading, watching and shipping into real work."
            :icon="GraduationCap"
        >
            <template #actions>
                <Button @click="createResource">
                    <Plus class="size-4" />
                    New resource
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

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="item in filters"
                    :key="item.value"
                    type="button"
                    :aria-pressed="filter === item.value"
                    :class="[
                        'rounded-full border px-3 py-1.5 text-xs font-medium transition',
                        filter === item.value
                            ? 'border-foreground bg-foreground text-background'
                            : 'bg-background text-muted-foreground hover:border-foreground/40 hover:text-foreground',
                    ]"
                    @click="filter = item.value"
                >
                    {{ item.label }}
                    <span class="ml-1 opacity-60">{{
                        filterCount(item.value)
                    }}</span>
                </button>
            </div>
            <div class="relative sm:ml-auto sm:w-64">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search learning..."
                />
            </div>
        </div>

        <div
            v-if="filtered.length"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="resource in filtered"
                :key="resource.id"
                class="group flex flex-col rounded-2xl border bg-card p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-300"
                        >
                            <component
                                :is="formatIcons[resource.format]"
                                class="size-4"
                            />
                        </div>
                        <div>
                            <p
                                class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                {{ resource.category }} · {{ resource.format }}
                            </p>
                            <h2 class="mt-0.5 leading-tight font-semibold">
                                <a
                                    v-if="resource.url"
                                    :href="resource.url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="hover:underline"
                                >
                                    {{ resource.title }}
                                </a>
                                <span v-else>{{ resource.title }}</span>
                            </h2>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="-mr-2 text-muted-foreground"
                                :aria-label="`Actions for ${resource.title}`"
                            >
                                <MoreHorizontal class="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-44">
                            <DropdownMenuItem @click="editResource(resource)">
                                <Pencil class="size-4" />
                                Edit resource
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="resource.status !== 'completed'"
                                @click="setProgress(resource, 100)"
                            >
                                <CheckCircle2 class="size-4" />
                                Mark complete
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-else
                                @click="setProgress(resource, 85)"
                            >
                                <RotateCcw class="size-4" />
                                Reopen resource
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                @click="removeResource(resource)"
                            >
                                <Trash2 class="size-4" />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <p class="mt-3 text-sm text-muted-foreground">
                    {{ resource.provider || 'Self-directed' }}
                </p>

                <p
                    v-if="resource.notes"
                    class="mt-3 line-clamp-2 rounded-lg border bg-muted/30 px-3 py-2 text-xs text-muted-foreground"
                >
                    {{ resource.notes }}
                </p>

                <div class="mt-auto pt-5">
                    <div class="mb-1.5 flex items-center justify-between">
                        <Badge
                            variant="outline"
                            :class="learningStatusClasses[resource.status]"
                        >
                            {{ learningStatusLabel[resource.status] }}
                        </Badge>
                        <span class="text-sm font-semibold">
                            {{ resource.progress }}%
                        </span>
                    </div>
                    <ProgressBar
                        :value="resource.progress"
                        :color="
                            resource.status === 'completed'
                                ? 'emerald'
                                : 'indigo'
                        "
                    />
                    <div class="mt-3 flex items-center gap-2">
                        <Button
                            v-if="resource.status !== 'completed'"
                            variant="outline"
                            size="sm"
                            class="flex-1"
                            @click="
                                setProgress(resource, resource.progress + 10)
                            "
                        >
                            <Plus class="size-3.5" />
                            Log 10%
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="sm"
                            class="flex-1"
                            @click="setProgress(resource, 85)"
                        >
                            <RotateCcw class="size-3.5" />
                            Reopen
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="editResource(resource)"
                        >
                            Update
                        </Button>
                    </div>
                </div>
            </article>
        </div>

        <EmptyState
            v-else
            :icon="GraduationCap"
            title="Nothing here yet"
            description="Add a course, book or article you want to make progress on."
        >
            <Button @click="createResource">
                <Plus class="size-4" />
                New resource
            </Button>
        </EmptyState>
    </div>

    <LearningDialog v-model:open="dialogOpen" :resource="editing" />
</template>
