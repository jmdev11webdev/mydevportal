<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    CalendarDays,
    CheckCircle2,
    Circle,
    Flame,
    FolderKanban,
    GraduationCap,
    ListChecks,
    Lock,
    NotebookPen,
    Plus,
    Sparkles,
    Target,
    TrendingUp,
} from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StatCard from '@/components/StatCard.vue';
import NoteDialog from '@/components/portal/NoteDialog.vue';
import TaskDialog from '@/components/portal/TaskDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    careerStatusClasses,
    careerStatusLabel,
    noteColorClasses,
    projectColorClasses,
    projectStatusClasses,
    projectStatusLabel,
} from '@/lib/colors';
import { isOverdue, relativeDate, todayInput } from '@/lib/format';
import type {
    CareerEntry,
    LearningResource,
    Note,
    Project,
    Task,
} from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
    },
});

const props = defineProps<{
    stats: {
        open_tasks: number;
        done_this_week: number;
        active_projects: number;
        learning_in_progress: number;
        overdue_tasks: number;
        notes: number;
        career_active: number;
        streak: number;
    };
    todayTasks: Task[];
    upcomingTasks: Task[];
    projects: Project[];
    learning: LearningResource[];
    notes: Note[];
    career: CareerEntry[];
    week: { label: string; date: string; count: number }[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const taskDialogOpen = ref(false);
const noteDialogOpen = ref(false);

const quickCapture = useForm({
    title: '',
    status: 'todo',
    priority: 'medium',
    due_date: todayInput(),
    project_id: null as number | null,
});

const hour = new Date().getHours();
const greeting =
    hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';

const firstName = computed(() => user.value.name.split(' ')[0] ?? 'there');

const dateLabel = new Intl.DateTimeFormat(undefined, {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
}).format(new Date());

const todayDate = todayInput();

const focusTasks = computed(() =>
    props.todayTasks.length ? props.todayTasks : props.upcomingTasks,
);

const focusTitle = computed(() =>
    props.todayTasks.length ? "Today's focus" : 'Coming up next',
);

const weekMax = computed(() =>
    Math.max(1, ...props.week.map((day) => day.count)),
);

const weekTotal = computed(() =>
    props.week.reduce((total, day) => total + day.count, 0),
);

function captureTask(): void {
    if (!quickCapture.title.trim()) {
        return;
    }

    quickCapture.post('/tasks', {
        preserveScroll: true,
        onSuccess: () => {
            quickCapture.reset('title');
            quickCapture.due_date = todayInput();
        },
    });
}

function toggleTask(task: Task): void {
    router.patch(`/tasks/${task.id}/toggle`, {}, { preserveScroll: true });
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
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:gap-5 md:p-6">
        <PageHeader
            eyebrow="Overview"
            :title="`${greeting}, ${firstName}`"
            :description="dateLabel"
        >
            <template #actions>
                <Badge
                    variant="outline"
                    class="hidden gap-1.5 border-emerald-500/30 bg-emerald-500/10 text-emerald-700 sm:flex dark:text-emerald-300"
                >
                    <Lock class="size-3" />
                    Private workspace
                </Badge>
                <Button variant="outline" @click="noteDialogOpen = true">
                    <NotebookPen class="size-4" />
                    Quick note
                </Button>
                <Button @click="taskDialogOpen = true">
                    <Plus class="size-4" />
                    New task
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 lg:grid-cols-3">
            <section
                class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-6 text-white shadow-lg lg:col-span-2"
            >
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.07]"
                    style="
                        background-image:
                            linear-gradient(
                                to right,
                                white 1px,
                                transparent 1px
                            ),
                            linear-gradient(
                                to bottom,
                                white 1px,
                                transparent 1px
                            );
                        background-size: 32px 32px;
                    "
                />
                <div
                    class="pointer-events-none absolute -top-24 -right-16 size-64 rounded-full bg-indigo-500/20 blur-3xl"
                />

                <div class="relative">
                    <div
                        class="flex items-center gap-2 text-xs text-indigo-200"
                    >
                        <Sparkles class="size-3.5" />
                        <span class="tracking-[0.14em] uppercase">
                            Your developer HQ
                        </span>
                    </div>
                    <h2
                        class="mt-4 max-w-lg text-2xl font-semibold tracking-tight"
                    >
                        Build, learn and grow — without handing over your data.
                    </h2>
                    <p class="mt-2 max-w-md text-sm text-slate-300">
                        Capture the next step, keep your projects honest, and
                        make room for the work that compounds.
                    </p>

                    <form class="mt-6" @submit.prevent="captureTask">
                        <div
                            class="flex flex-col gap-2 rounded-xl border border-white/10 bg-white/5 p-2 backdrop-blur sm:flex-row sm:items-center"
                        >
                            <div class="flex flex-1 items-center gap-2 px-2">
                                <Plus class="size-4 text-slate-400" />
                                <Input
                                    v-model="quickCapture.title"
                                    placeholder="Capture a task for today..."
                                    class="h-9 border-0 bg-transparent px-0 text-white shadow-none placeholder:text-slate-400 focus-visible:ring-0 dark:bg-transparent"
                                />
                            </div>
                            <Button
                                type="submit"
                                :disabled="quickCapture.processing"
                                class="bg-white text-slate-900 hover:bg-slate-200"
                            >
                                Add task
                                <ArrowRight class="size-4" />
                            </Button>
                        </div>
                        <p
                            v-if="quickCapture.errors.title"
                            class="mt-2 text-xs text-rose-300"
                        >
                            {{ quickCapture.errors.title }}
                        </p>
                    </form>

                    <div class="mt-6 flex flex-wrap gap-2">
                        <Link
                            href="/tasks"
                            class="flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-200 transition hover:bg-white/10"
                        >
                            <ListChecks class="size-3.5" />
                            {{ stats.open_tasks }} open tasks
                        </Link>
                        <Link
                            href="/projects"
                            class="flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-200 transition hover:bg-white/10"
                        >
                            <FolderKanban class="size-3.5" />
                            {{ stats.active_projects }} active projects
                        </Link>
                        <Link
                            href="/learning"
                            class="flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-200 transition hover:bg-white/10"
                        >
                            <GraduationCap class="size-3.5" />
                            {{ stats.learning_in_progress }} learning
                        </Link>
                    </div>
                </div>
            </section>

            <section
                class="flex flex-col rounded-2xl border bg-card p-5 shadow-sm"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p
                            class="text-[11px] font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                        >
                            This week
                        </p>
                        <p class="mt-1 text-2xl font-semibold tracking-tight">
                            {{ weekTotal }} tasks shipped
                        </p>
                    </div>
                    <div
                        class="flex size-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-300"
                    >
                        <TrendingUp class="size-4" />
                    </div>
                </div>

                <div class="mt-5 flex flex-1 items-end gap-2">
                    <div
                        v-for="day in week"
                        :key="day.date"
                        class="flex flex-1 flex-col items-center gap-2"
                    >
                        <div
                            class="flex h-24 w-full items-end rounded-md bg-muted/40"
                        >
                            <div
                                :class="[
                                    'w-full rounded-md transition-all',
                                    day.count > 0
                                        ? 'bg-gradient-to-t from-indigo-600 to-indigo-400'
                                        : 'h-1 bg-muted-foreground/20',
                                    day.date === todayDate
                                        ? 'ring-2 ring-indigo-400/40'
                                        : '',
                                ]"
                                :style="
                                    day.count > 0
                                        ? {
                                              height: `${Math.max(
                                                  10,
                                                  (day.count / weekMax) * 100,
                                              )}%`,
                                          }
                                        : undefined
                                "
                            />
                        </div>
                        <span
                            :class="[
                                'text-[10px]',
                                day.date === todayDate
                                    ? 'font-semibold text-foreground'
                                    : 'text-muted-foreground',
                            ]"
                        >
                            {{ day.label }}
                        </span>
                    </div>
                </div>

                <div
                    class="mt-5 flex items-center justify-between rounded-xl border bg-muted/30 px-3 py-2.5"
                >
                    <div class="flex items-center gap-2">
                        <Flame class="size-4 text-amber-500" />
                        <span class="text-xs font-medium">
                            {{ stats.streak }}-day focus streak
                        </span>
                    </div>
                    <span class="text-[11px] text-muted-foreground">
                        {{ stats.done_this_week }} done this week
                    </span>
                </div>
            </section>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <StatCard
                label="Open tasks"
                :value="stats.open_tasks"
                :hint="
                    stats.overdue_tasks
                        ? `${stats.overdue_tasks} overdue`
                        : 'All on track'
                "
                :icon="ListChecks"
                tone="indigo"
            />
            <StatCard
                label="Done this week"
                :value="stats.done_this_week"
                hint="Keep the streak alive"
                :icon="CheckCircle2"
                tone="emerald"
            />
            <StatCard
                label="Active projects"
                :value="stats.active_projects"
                hint="Builds in motion"
                :icon="FolderKanban"
                tone="amber"
            />
            <StatCard
                label="Learning"
                :value="stats.learning_in_progress"
                hint="Resources in progress"
                :icon="GraduationCap"
                tone="rose"
            />
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="flex flex-col gap-4 lg:col-span-2">
                <section class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <CalendarDays
                                class="size-4 text-muted-foreground"
                            />
                            <h2 class="text-sm font-semibold">
                                {{ focusTitle }}
                            </h2>
                        </div>
                        <Link
                            href="/tasks"
                            class="flex items-center gap-1 text-xs font-medium text-muted-foreground transition hover:text-foreground"
                        >
                            View board
                            <ArrowUpRight class="size-3.5" />
                        </Link>
                    </div>

                    <div
                        v-if="focusTasks.length"
                        class="mt-4 flex flex-col divide-y"
                    >
                        <div
                            v-for="task in focusTasks"
                            :key="task.id"
                            class="group flex items-start gap-3 py-3 first:pt-0 last:pb-0"
                        >
                            <button
                                type="button"
                                class="mt-0.5 text-muted-foreground transition hover:text-emerald-500"
                                aria-label="Complete task"
                                @click="toggleTask(task)"
                            >
                                <Circle class="size-5" />
                            </button>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-snug font-medium">
                                    {{ task.title }}
                                </p>
                                <div
                                    class="mt-1.5 flex flex-wrap items-center gap-2"
                                >
                                    <span
                                        v-if="task.project"
                                        class="flex items-center gap-1.5 rounded-md border bg-muted/40 px-1.5 py-0.5 text-[11px]"
                                    >
                                        <span
                                            :class="[
                                                'size-1.5 rounded-full',
                                                projectColorClasses[
                                                    task.project.color
                                                ].dot,
                                            ]"
                                        />
                                        {{ task.project.name }}
                                    </span>
                                    <span
                                        v-if="task.due_date"
                                        :class="[
                                            'text-[11px]',
                                            isOverdue(task.due_date)
                                                ? 'font-medium text-rose-500'
                                                : 'text-muted-foreground',
                                        ]"
                                    >
                                        {{ relativeDate(task.due_date) }}
                                    </span>
                                    <span
                                        class="text-[11px] text-muted-foreground capitalize"
                                    >
                                        · {{ task.priority }} priority
                                    </span>
                                </div>
                            </div>
                            <Badge
                                variant="outline"
                                class="hidden shrink-0 sm:flex"
                            >
                                {{
                                    task.status === 'in_progress'
                                        ? 'Doing'
                                        : 'To do'
                                }}
                            </Badge>
                        </div>
                    </div>
                    <div
                        v-else
                        class="mt-4 rounded-xl border border-dashed py-8 text-center"
                    >
                        <p class="text-sm font-medium">Nothing due today</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Use the capture box above to add the next step.
                        </p>
                    </div>
                </section>

                <section class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <FolderKanban
                                class="size-4 text-muted-foreground"
                            />
                            <h2 class="text-sm font-semibold">
                                Active projects
                            </h2>
                        </div>
                        <Link
                            href="/projects"
                            class="flex items-center gap-1 text-xs font-medium text-muted-foreground transition hover:text-foreground"
                        >
                            All projects
                            <ArrowUpRight class="size-3.5" />
                        </Link>
                    </div>

                    <div
                        v-if="projects.length"
                        class="mt-4 grid gap-3 sm:grid-cols-2"
                    >
                        <Link
                            v-for="project in projects"
                            :key="project.id"
                            href="/projects"
                            class="group rounded-xl border bg-muted/20 p-4 transition hover:border-foreground/20 hover:bg-muted/40"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="[
                                            'size-2 rounded-full',
                                            projectColorClasses[project.color]
                                                .dot,
                                        ]"
                                    />
                                    <p class="text-sm font-semibold">
                                        {{ project.name }}
                                    </p>
                                </div>
                                <Badge
                                    variant="outline"
                                    class="shrink-0 text-[10px]"
                                    :class="
                                        projectStatusClasses[project.status]
                                    "
                                >
                                    {{ projectStatusLabel[project.status] }}
                                </Badge>
                            </div>
                            <p
                                class="mt-2 line-clamp-1 text-xs text-muted-foreground"
                            >
                                {{
                                    project.description || 'No description yet.'
                                }}
                            </p>
                            <div class="mt-3">
                                <div
                                    class="mb-1.5 flex items-center justify-between text-[11px] text-muted-foreground"
                                >
                                    <span>
                                        {{
                                            project.completed_tasks_count ?? 0
                                        }}/{{ project.tasks_count ?? 0 }}
                                        tasks
                                    </span>
                                    <span>{{ progressFor(project) }}%</span>
                                </div>
                                <ProgressBar
                                    :value="progressFor(project)"
                                    :color="
                                        projectColorClasses[project.color].bar
                                    "
                                />
                            </div>
                        </Link>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        No active projects. Start something small this week.
                    </p>
                </section>
            </div>

            <div class="flex flex-col gap-4">
                <section class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <GraduationCap
                                class="size-4 text-muted-foreground"
                            />
                            <h2 class="text-sm font-semibold">
                                Continue learning
                            </h2>
                        </div>
                        <Link
                            href="/learning"
                            class="text-xs text-muted-foreground transition hover:text-foreground"
                        >
                            View
                        </Link>
                    </div>

                    <div v-if="learning.length" class="mt-4 space-y-4">
                        <Link
                            v-for="resource in learning"
                            :key="resource.id"
                            href="/learning"
                            class="block"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="line-clamp-1 text-sm font-medium">
                                        {{ resource.title }}
                                    </p>
                                    <p
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        {{
                                            resource.provider ||
                                            resource.category
                                        }}
                                    </p>
                                </div>
                                <span class="text-xs font-semibold">
                                    {{ resource.progress }}%
                                </span>
                            </div>
                            <ProgressBar
                                class="mt-2"
                                :value="resource.progress"
                                color="indigo"
                            />
                        </Link>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Nothing in progress. Pick one resource and start today.
                    </p>
                </section>

                <section class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <Target class="size-4 text-muted-foreground" />
                            <h2 class="text-sm font-semibold">
                                Career snapshot
                            </h2>
                        </div>
                        <Link
                            href="/career"
                            class="text-xs text-muted-foreground transition hover:text-foreground"
                        >
                            View
                        </Link>
                    </div>

                    <div v-if="career.length" class="mt-4 space-y-3">
                        <Link
                            v-for="entry in career"
                            :key="entry.id"
                            href="/career"
                            class="flex items-start justify-between gap-3 rounded-lg border bg-muted/20 px-3 py-2.5 transition hover:bg-muted/40"
                        >
                            <div class="min-w-0">
                                <p class="line-clamp-1 text-xs font-semibold">
                                    {{ entry.title }}
                                </p>
                                <p
                                    v-if="entry.organization"
                                    class="text-[10px] text-muted-foreground"
                                >
                                    {{ entry.organization }}
                                </p>
                            </div>
                            <Badge
                                variant="outline"
                                class="shrink-0 text-[10px]"
                                :class="careerStatusClasses[entry.status]"
                            >
                                {{ careerStatusLabel[entry.status] }}
                            </Badge>
                        </Link>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Add a goal to keep your career moving deliberately.
                    </p>
                </section>

                <section class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <NotebookPen class="size-4 text-muted-foreground" />
                            <h2 class="text-sm font-semibold">Recent notes</h2>
                        </div>
                        <Link
                            href="/notes"
                            class="text-xs text-muted-foreground transition hover:text-foreground"
                        >
                            View all
                        </Link>
                    </div>

                    <div v-if="notes.length" class="mt-4 space-y-2">
                        <Link
                            v-for="note in notes"
                            :key="note.id"
                            href="/notes"
                            :class="[
                                'block rounded-lg border px-3 py-2.5 transition hover:opacity-90',
                                noteColorClasses[note.color].card,
                            ]"
                        >
                            <p class="line-clamp-1 text-xs font-semibold">
                                {{ note.title }}
                            </p>
                            <p
                                class="mt-0.5 line-clamp-1 font-mono text-[10px] text-muted-foreground"
                            >
                                {{ note.body }}
                            </p>
                        </Link>
                    </div>
                    <p v-else class="mt-4 text-sm text-muted-foreground">
                        Your private notes will show up here.
                    </p>
                </section>
            </div>
        </div>
    </div>

    <TaskDialog v-model:open="taskDialogOpen" :projects="projects" />
    <NoteDialog v-model:open="noteDialogOpen" />
</template>
