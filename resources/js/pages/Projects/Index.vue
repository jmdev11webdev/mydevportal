<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    ExternalLink,
    FileCode,
    FolderKanban,
    GitBranch,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    Trash2,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import ProjectDialog from '@/components/portal/ProjectDialog.vue';
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
import {
    projectColorClasses,
    projectStatusClasses,
    projectStatusLabel,
} from '@/lib/colors';
import { formatShortDate, relativeDate } from '@/lib/format';
import type { Project, ProjectStatus } from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Projects', href: '/projects' }],
    },
});

const props = defineProps<{
    projects: Project[];
}>();

const dialogOpen = ref(false);
const editing = ref<Project | null>(null);
const filter = ref<'all' | ProjectStatus>('all');
const search = ref('');

const filters: { label: string; value: 'all' | ProjectStatus }[] = [
    { label: 'All', value: 'all' },
    { label: 'Active', value: 'active' },
    { label: 'Planning', value: 'planning' },
    { label: 'Paused', value: 'paused' },
    { label: 'Completed', value: 'completed' },
];

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.projects.filter((project) => {
        const matchesStatus =
            filter.value === 'all' || project.status === filter.value;
        const matchesSearch =
            !term ||
            project.name.toLowerCase().includes(term) ||
            (project.description ?? '').toLowerCase().includes(term) ||
            (project.tech_stack ?? []).some((tech) =>
                tech.toLowerCase().includes(term),
            );

        return matchesStatus && matchesSearch;
    });
});

function filterCount(value: 'all' | ProjectStatus): number {
    if (value === 'all') {
        return props.projects.length;
    }

    return props.projects.filter((project) => project.status === value).length;
}

function createProject(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function editProject(project: Project): void {
    editing.value = project;
    dialogOpen.value = true;
}

function generateReadme(project: Project): void {
    router.visit(`/readme?project=${project.id}`);
}

function removeProject(project: Project): void {
    if (
        !window.confirm(
            `Delete "${project.name}"? Its tasks will be kept but unlinked.`,
        )
    ) {
        return;
    }

    router.delete(`/projects/${project.id}`, { preserveScroll: true });
}

function progressFor(project: Project): number {
    if (!project.tasks_count) {
        return 0;
    }

    return Math.round(
        ((project.completed_tasks_count ?? 0) / project.tasks_count) * 100,
    );
}
</script>

<template>
    <Head title="Projects" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Build"
            title="Projects"
            description="Every build, experiment and long-running idea — with the progress that actually matters."
            :icon="FolderKanban"
        >
            <template #actions>
                <Button @click="createProject">
                    <Plus class="size-4" />
                    New project
                </Button>
            </template>
        </PageHeader>

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
                    <span class="ml-1 opacity-60">
                        {{ filterCount(item.value) }}
                    </span>
                </button>
            </div>
            <div class="relative sm:ml-auto sm:w-64">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search projects or stack..."
                />
            </div>
        </div>

        <div
            v-if="filtered.length"
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="project in filtered"
                :key="project.id"
                class="group relative flex flex-col rounded-2xl border bg-card p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
            >
                <div
                    :class="[
                        'absolute inset-x-0 top-0 h-1 rounded-t-2xl',
                        projectColorClasses[project.color].dot,
                    ]"
                />

                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'flex size-9 items-center justify-center rounded-xl',
                                projectColorClasses[project.color].soft,
                                projectColorClasses[project.color].text,
                            ]"
                        >
                            <FolderKanban class="size-4" />
                        </div>
                        <div>
                            <h2 class="leading-tight font-semibold">
                                {{ project.name }}
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{ project.tasks_count ?? 0 }} tasks ·
                                {{ progressFor(project) }}% done
                            </p>
                        </div>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="-mr-2 text-muted-foreground"
                                :aria-label="`Actions for ${project.name}`"
                            >
                                <MoreHorizontal class="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-44">
                            <DropdownMenuItem @click="editProject(project)">
                                <Pencil class="size-4" />
                                Edit project
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="generateReadme(project)">
                                <FileCode class="size-4" />
                                Generate README
                            </DropdownMenuItem>
                            <DropdownMenuItem
                                v-if="project.repository_url"
                                as-child
                            >
                                <a
                                    :href="project.repository_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <GitBranch class="size-4" />
                                    Open repository
                                </a>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                variant="destructive"
                                @click="removeProject(project)"
                            >
                                <Trash2 class="size-4" />
                                Delete project
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <p
                    class="mt-4 line-clamp-2 min-h-10 text-sm text-muted-foreground"
                >
                    {{ project.description || 'No description yet.' }}
                </p>

                <div class="mt-4 flex flex-wrap gap-1.5">
                    <span
                        v-for="tech in project.tech_stack ?? []"
                        :key="tech"
                        class="rounded-md border bg-muted/50 px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                    >
                        {{ tech }}
                    </span>
                    <span
                        v-if="!(project.tech_stack ?? []).length"
                        class="text-[11px] text-muted-foreground"
                    >
                        No stack added
                    </span>
                </div>

                <div class="mt-auto pt-5">
                    <div class="mb-2 flex items-center justify-between">
                        <Badge
                            variant="outline"
                            :class="projectStatusClasses[project.status]"
                        >
                            {{ projectStatusLabel[project.status] }}
                        </Badge>
                        <span
                            v-if="project.due_at"
                            class="text-[11px] text-muted-foreground"
                        >
                            {{ relativeDate(project.due_at) }}
                        </span>
                    </div>
                    <ProgressBar
                        :value="progressFor(project)"
                        :color="projectColorClasses[project.color].bar"
                    />
                    <div
                        class="mt-2 flex items-center justify-between text-[11px] text-muted-foreground"
                    >
                        <span>
                            {{ project.completed_tasks_count ?? 0 }} of
                            {{ project.tasks_count ?? 0 }} complete
                        </span>
                        <span v-if="project.due_at">
                            {{ formatShortDate(project.due_at) }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <Button
                        v-if="project.live_url"
                        variant="outline"
                        size="sm"
                        as-child
                        class="flex-1"
                    >
                        <a
                            :href="project.live_url"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <ExternalLink class="size-3.5" />
                            Live site
                        </a>
                    </Button>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        class="flex-1"
                        @click="editProject(project)"
                    >
                        <ArrowUpRight class="size-3.5" />
                        Open project
                    </Button>
                </div>
            </article>
        </div>

        <EmptyState
            v-else
            :icon="FolderKanban"
            title="No projects in this view"
            description="Start a new build or switch filters to see everything you're working on."
        >
            <Button @click="createProject">
                <Plus class="size-4" />
                New project
            </Button>
        </EmptyState>
    </div>

    <ProjectDialog v-model:open="dialogOpen" :project="editing" />
</template>
