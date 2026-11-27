<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BarChart3,
    Briefcase,
    CalendarDays,
    CheckCircle2,
    Download,
    Flame,
    FolderKanban,
    GraduationCap,
    Layers,
    ListChecks,
    NotebookPen,
    Target,
    TrendingUp,
} from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StatCard from '@/components/StatCard.vue';
import { Badge } from '@/components/ui/badge';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Statistics', href: '/statistics' }],
    },
});

type StatisticsPayload = {
    projects: {
        total: number;
        active: number;
        planning: number;
        paused: number;
        completed: number;
        completion_rate: number;
    };
    tasks: {
        total: number;
        done: number;
        open: number;
        overdue: number;
        high_priority_open: number;
        completion_rate: number;
        subtasks_total: number;
        subtasks_done: number;
    };
    pipeline: {
        total: number;
        active: number;
        won: number;
        lost: number;
        win_rate: number;
        active_value: number;
        won_value: number;
        total_value: number;
        by_stage: { stage: string; count: number; value: number }[];
    };
    career: {
        total: number;
        planned: number;
        in_progress: number;
        achieved: number;
        archived: number;
        goals: number;
        applications: number;
        milestones: number;
    };
    learning: {
        total: number;
        planned: number;
        in_progress: number;
        completed: number;
        avg_progress: number;
    };
    stack: {
        total: number;
        core: number;
        proficient: number;
        learning: number;
        evaluating: number;
        retired: number;
    };
    workspace: {
        notes: number;
        pinned_notes: number;
        events: number;
        upcoming_events: number;
    };
    activity: {
        streak: number;
        done_this_week: number;
        done_this_month: number;
        weekly_trend: { label: string; date: string; count: number }[];
    };
    downloads: {
        total: number;
        exe: number;
        apk: number;
        releases: number;
        latest_tag: string | null;
    };
};

const props = defineProps<{
    statistics: StatisticsPayload;
}>();

const stageLabels: Record<string, string> = {
    new: 'New',
    contacted: 'Contacted',
    proposal: 'Proposal',
    won: 'Won',
    lost: 'Lost',
};

const stageColors: Record<string, string> = {
    new: 'bg-sky-500',
    contacted: 'bg-amber-500',
    proposal: 'bg-indigo-500',
    won: 'bg-emerald-500',
    lost: 'bg-rose-500',
};

const weeklyMax = computed(() =>
    Math.max(1, ...props.statistics.activity.weekly_trend.map((d) => d.count)),
);

function formatCurrency(value: number): string {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(value);
}
</script>

<template>
    <Head title="Overall Statistics" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Growth & CRM"
            title="Overall Statistics"
            description="Cross-portal telemetry across your pipeline, career, learning, projects, and daily execution."
            :icon="BarChart3"
        />

        <!-- Top Summary KPIs -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Task completion"
                :value="`${statistics.tasks.completion_rate}%`"
                :hint="`${statistics.tasks.done} of ${statistics.tasks.total} tasks shipped`"
                :icon="CheckCircle2"
                tone="emerald"
            />
            <StatCard
                label="Pipeline won value"
                :value="formatCurrency(statistics.pipeline.won_value)"
                :hint="`${statistics.pipeline.won} won · ${statistics.pipeline.win_rate}% win rate`"
                :icon="TrendingUp"
                tone="indigo"
            />
            <StatCard
                label="Learning progress"
                :value="`${statistics.learning.avg_progress}%`"
                :hint="`${statistics.learning.completed} completed of ${statistics.learning.total}`"
                :icon="GraduationCap"
                tone="amber"
            />
            <StatCard
                label="Shipping streak"
                :value="`${statistics.activity.streak}d`"
                :hint="`${statistics.activity.done_this_month} tasks done this month`"
                :icon="Flame"
                tone="rose"
            />
        </div>

        <!-- Pipeline & Weekly Execution -->
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <Briefcase class="size-4 text-indigo-500" />
                        <h2 class="text-sm font-semibold">
                            CRM Pipeline Breakdown
                        </h2>
                    </div>
                    <Link
                        href="/pipeline"
                        class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition hover:text-foreground"
                    >
                        Open pipeline
                        <ArrowUpRight class="size-3.5" />
                    </Link>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-xl border bg-muted/20 p-3">
                        <p class="text-[11px] text-muted-foreground">
                            Active value
                        </p>
                        <p class="mt-1 text-lg font-semibold tabular-nums">
                            {{
                                formatCurrency(statistics.pipeline.active_value)
                            }}
                        </p>
                    </div>
                    <div class="rounded-xl border bg-muted/20 p-3">
                        <p class="text-[11px] text-muted-foreground">
                            Total pipeline
                        </p>
                        <p class="mt-1 text-lg font-semibold tabular-nums">
                            {{
                                formatCurrency(statistics.pipeline.total_value)
                            }}
                        </p>
                    </div>
                    <div class="rounded-xl border bg-muted/20 p-3">
                        <p class="text-[11px] text-muted-foreground">
                            Win rate
                        </p>
                        <p class="mt-1 text-lg font-semibold tabular-nums">
                            {{ statistics.pipeline.win_rate }}%
                        </p>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <div
                        v-for="stage in statistics.pipeline.by_stage"
                        :key="stage.stage"
                        class="space-y-1"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'size-2 rounded-full',
                                        stageColors[stage.stage] ??
                                            'bg-slate-400',
                                    ]"
                                />
                                <span class="font-medium">
                                    {{
                                        stageLabels[stage.stage] ?? stage.stage
                                    }}
                                </span>
                                <span class="text-muted-foreground">
                                    ({{ stage.count }})
                                </span>
                            </div>
                            <span class="font-mono text-muted-foreground">
                                {{ formatCurrency(stage.value) }}
                            </span>
                        </div>
                        <div
                            class="h-1.5 overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                :class="[
                                    'h-full rounded-full transition-all',
                                    stageColors[stage.stage] ?? 'bg-slate-400',
                                ]"
                                :style="{
                                    width: `${
                                        statistics.pipeline.total > 0
                                            ? Math.round(
                                                  (stage.count /
                                                      statistics.pipeline
                                                          .total) *
                                                      100,
                                              )
                                            : 0
                                    }%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="flex flex-col justify-between rounded-2xl border bg-card p-5 shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <ListChecks class="size-4 text-emerald-500" />
                            <h2 class="text-sm font-semibold">
                                Execution &amp; 7-Day Velocity
                            </h2>
                        </div>
                        <Link
                            href="/tasks"
                            class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition hover:text-foreground"
                        >
                            Focus board
                            <ArrowUpRight class="size-3.5" />
                        </Link>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="rounded-xl border bg-muted/20 p-3">
                            <p class="text-[11px] text-muted-foreground">
                                Open tasks
                            </p>
                            <p class="mt-1 text-lg font-semibold tabular-nums">
                                {{ statistics.tasks.open }}
                            </p>
                        </div>
                        <div class="rounded-xl border bg-muted/20 p-3">
                            <p class="text-[11px] text-muted-foreground">
                                Overdue
                            </p>
                            <p
                                class="mt-1 text-lg font-semibold tabular-nums"
                                :class="
                                    statistics.tasks.overdue > 0
                                        ? 'text-rose-500'
                                        : ''
                                "
                            >
                                {{ statistics.tasks.overdue }}
                            </p>
                        </div>
                        <div class="rounded-xl border bg-muted/20 p-3">
                            <p class="text-[11px] text-muted-foreground">
                                Subtasks done
                            </p>
                            <p class="mt-1 text-lg font-semibold tabular-nums">
                                {{ statistics.tasks.subtasks_done }}/{{
                                    statistics.tasks.subtasks_total
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-end gap-2">
                    <div
                        v-for="day in statistics.activity.weekly_trend"
                        :key="day.date"
                        class="flex flex-1 flex-col items-center gap-2"
                    >
                        <div
                            class="flex h-28 w-full items-end rounded-md bg-muted/40"
                        >
                            <div
                                :class="[
                                    'w-full rounded-md transition-all',
                                    day.count > 0
                                        ? 'bg-gradient-to-t from-emerald-600 to-emerald-400'
                                        : 'h-1 bg-muted-foreground/20',
                                ]"
                                :style="
                                    day.count > 0
                                        ? {
                                              height: `${Math.max(
                                                  12,
                                                  (day.count / weeklyMax) * 100,
                                              )}%`,
                                          }
                                        : undefined
                                "
                            />
                        </div>
                        <span class="text-[10px] text-muted-foreground">
                            {{ day.label }}
                        </span>
                    </div>
                </div>
            </section>
        </div>

        <!-- Projects, Career, Learning, Stack & Workspace -->
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <FolderKanban class="size-4 text-indigo-500" />
                        <h3 class="text-sm font-semibold">Projects</h3>
                    </div>
                    <Badge variant="outline" class="text-[11px]">
                        {{ statistics.projects.total }} total
                    </Badge>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-muted-foreground">Shipped rate</span>
                        <span class="font-semibold">
                            {{ statistics.projects.completion_rate }}%
                        </span>
                    </div>
                    <ProgressBar
                        :value="statistics.projects.completion_rate"
                        class="mt-2"
                    />
                </div>
                <dl class="mt-4 space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Active</dt>
                        <dd class="font-medium">
                            {{ statistics.projects.active }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Planning</dt>
                        <dd class="font-medium">
                            {{ statistics.projects.planning }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Completed</dt>
                        <dd class="font-medium">
                            {{ statistics.projects.completed }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Target class="size-4 text-rose-500" />
                        <h3 class="text-sm font-semibold">Career</h3>
                    </div>
                    <Badge variant="outline" class="text-[11px]">
                        {{ statistics.career.total }} entries
                    </Badge>
                </div>
                <dl class="mt-4 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Achieved</dt>
                        <dd
                            class="font-semibold text-emerald-600 dark:text-emerald-400"
                        >
                            {{ statistics.career.achieved }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">In progress</dt>
                        <dd class="font-medium">
                            {{ statistics.career.in_progress }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Planned</dt>
                        <dd class="font-medium">
                            {{ statistics.career.planned }}
                        </dd>
                    </div>
                    <div class="flex justify-between border-t pt-2">
                        <dt class="text-muted-foreground">
                            Goals / Apps / Milestones
                        </dt>
                        <dd class="font-mono text-[11px]">
                            {{ statistics.career.goals }} /
                            {{ statistics.career.applications }} /
                            {{ statistics.career.milestones }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Layers class="size-4 text-amber-500" />
                        <h3 class="text-sm font-semibold">Tech Stack</h3>
                    </div>
                    <Badge variant="outline" class="text-[11px]">
                        {{ statistics.stack.total }} tools
                    </Badge>
                </div>
                <dl class="mt-4 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Core</dt>
                        <dd class="font-semibold">
                            {{ statistics.stack.core }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Proficient</dt>
                        <dd class="font-medium">
                            {{ statistics.stack.proficient }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Learning</dt>
                        <dd class="font-medium">
                            {{ statistics.stack.learning }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Evaluating</dt>
                        <dd class="font-medium">
                            {{ statistics.stack.evaluating }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <NotebookPen class="size-4 text-sky-500" />
                        <h3 class="text-sm font-semibold">Workspace</h3>
                    </div>
                    <CalendarDays class="size-4 text-muted-foreground" />
                </div>
                <dl class="mt-4 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Total notes</dt>
                        <dd class="font-semibold">
                            {{ statistics.workspace.notes }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Pinned notes</dt>
                        <dd class="font-medium">
                            {{ statistics.workspace.pinned_notes }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Calendar events</dt>
                        <dd class="font-medium">
                            {{ statistics.workspace.events }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Upcoming events</dt>
                        <dd class="font-medium">
                            {{ statistics.workspace.upcoming_events }}
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <!-- Release Downloads (GitHub) -->
        <section class="rounded-2xl border bg-card p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <Download class="size-4 text-emerald-500" />
                    <h2 class="text-sm font-semibold">App Release Downloads</h2>
                    <Badge
                        v-if="statistics.downloads.latest_tag"
                        variant="outline"
                        class="text-[11px]"
                    >
                        {{ statistics.downloads.latest_tag }}
                    </Badge>
                </div>
                <a
                    href="https://github.com/jmdev11webdev/mydevportal/releases"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground transition hover:text-foreground"
                >
                    View releases
                    <ArrowUpRight class="size-3.5" />
                </a>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border bg-muted/20 p-3">
                    <p class="text-[11px] text-muted-foreground">
                        Total downloads
                    </p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">
                        {{ statistics.downloads.total.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-xl border bg-muted/20 p-3">
                    <p class="text-[11px] text-muted-foreground">
                        Desktop (.exe)
                    </p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">
                        {{ statistics.downloads.exe.toLocaleString() }}
                    </p>
                </div>
                <div class="rounded-xl border bg-muted/20 p-3">
                    <p class="text-[11px] text-muted-foreground">
                        Mobile (.apk)
                    </p>
                    <p class="mt-1 text-xl font-semibold tabular-nums">
                        {{ statistics.downloads.apk.toLocaleString() }}
                    </p>
                </div>
            </div>
        </section>
    </div>
</template>
