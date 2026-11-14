<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Calendar as CalendarIcon,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Circle,
    Clock,
    Eye,
    FolderKanban,
    Hourglass,
    ListChecks,
    ListTodo,
    MapPin,
    Pause,
    Pencil,
    Play,
    Plus,
    RotateCcw,
    Sparkles,
    Target,
    Timer,
    Trash2,
    X,
} from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StatCard from '@/components/StatCard.vue';
import EventDialog from '@/components/portal/EventDialog.vue';
import TaskDialog from '@/components/portal/TaskDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useEyeCareTimer } from '@/composables/useEyeCareTimer';
import {
    calendarEventTypeClasses,
    calendarEventTypeLabel,
    careerTypeClasses,
    careerTypeLabel,
    projectColorClasses,
    taskPriorityClasses,
} from '@/lib/colors';
import { formatDate, toDateInput, todayInput } from '@/lib/format';
import type {
    CalendarEvent,
    CareerEntry,
    Project,
    Subtask,
    Task,
} from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Calendar', href: '/calendar' }],
    },
});

const props = defineProps<{
    events: CalendarEvent[];
    tasks: Task[];
    projects: Pick<
        Project,
        'id' | 'name' | 'color' | 'status' | 'started_at' | 'due_at'
    >[];
    career: Pick<
        CareerEntry,
        'id' | 'title' | 'type' | 'organization' | 'status' | 'target_date'
    >[];
}>();

const {
    phase: eyePhase,
    isRunning: eyeRunning,
    formattedTime: eyeFormattedTime,
    remainingSeconds: eyeRemainingSeconds,
    durationRunning,
    durationLabel,
    isDurationPausedByEyeBreak,
    formattedDurationRemaining,
    formattedDurationElapsed,
    isDurationDefaultState,
    toggleTimer: toggleEyeTimer,
    startDurationTimer,
    toggleDurationTimer,
    stopDurationTimer,
} = useEyeCareTimer();

const todayStr = todayInput();
const selectedDate = ref<string>(todayStr);
const viewMode = ref<'month' | 'agenda'>('month');
const projectFilter = ref<string>('all');

const showEvents = ref(true);
const showTasks = ref(true);
const showProjects = ref(true);
const showCareer = ref(true);

const initialDateObj = new Date();
const currentYear = ref(initialDateObj.getFullYear());
const currentMonth = ref(initialDateObj.getMonth()); // 0-indexed

const eventDialogOpen = ref(false);
const editingEvent = ref<CalendarEvent | null>(null);

const taskDialogOpen = ref(false);
const editingTask = ref<Task | null>(null);
const defaultTaskEventId = ref<number | null>(null);
const defaultTaskDueDate = ref<string | null>(todayStr);

const inlineEventTaskInputs = reactive<Record<number, string>>({});
const inlineSubtaskInputs = reactive<Record<number, string>>({});

const weekdayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const monthTitle = computed(() =>
    new Intl.DateTimeFormat(undefined, {
        month: 'long',
        year: 'numeric',
    }).format(new Date(currentYear.value, currentMonth.value, 1)),
);

const filteredEvents = computed(() =>
    props.events.filter((ev) => {
        if (projectFilter.value === 'all') {
            return true;
        }
        return String(ev.project_id) === projectFilter.value;
    }),
);

const filteredTasks = computed(() =>
    props.tasks.filter((task) => {
        if (projectFilter.value === 'all') {
            return true;
        }
        return String(task.project_id) === projectFilter.value;
    }),
);

const filteredProjects = computed(() =>
    props.projects.filter((project) => {
        if (projectFilter.value === 'all') {
            return true;
        }
        return String(project.id) === projectFilter.value;
    }),
);

function formatYmd(date: Date): string {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function eventOccursOnDate(event: CalendarEvent, dateStr: string): boolean {
    const start = toDateInput(event.starts_at);
    const end = toDateInput(event.ends_at) || start;
    if (!start) {
        return false;
    }
    return dateStr >= start && dateStr <= end;
}

function tasksInsideEvent(eventId: number): Task[] {
    return props.tasks.filter((t) => t.calendar_event_id === eventId);
}

type DayCell = {
    dateStr: string;
    dayNumber: number;
    inCurrentMonth: boolean;
    isToday: boolean;
    events: CalendarEvent[];
    tasks: Task[];
    projectMilestones: {
        id: string;
        kind: 'start' | 'due';
        project: Pick<
            Project,
            'id' | 'name' | 'color' | 'status' | 'started_at' | 'due_at'
        >;
    }[];
    careerMilestones: Pick<
        CareerEntry,
        'id' | 'title' | 'type' | 'organization' | 'status' | 'target_date'
    >[];
};

function buildDayData(
    dateStr: string,
    dayNumber: number,
    inCurrentMonth: boolean,
): DayCell {
    const events = showEvents.value
        ? filteredEvents.value.filter((ev) => eventOccursOnDate(ev, dateStr))
        : [];

    const tasks = showTasks.value
        ? filteredTasks.value.filter((t) => toDateInput(t.due_date) === dateStr)
        : [];

    const projectMilestones: DayCell['projectMilestones'] = [];
    if (showProjects.value) {
        for (const proj of filteredProjects.value) {
            if (toDateInput(proj.started_at) === dateStr) {
                projectMilestones.push({
                    id: `start-${proj.id}`,
                    kind: 'start',
                    project: proj,
                });
            }
            if (toDateInput(proj.due_at) === dateStr) {
                projectMilestones.push({
                    id: `due-${proj.id}`,
                    kind: 'due',
                    project: proj,
                });
            }
        }
    }

    const careerMilestones = showCareer.value
        ? props.career.filter((c) => toDateInput(c.target_date) === dateStr)
        : [];

    return {
        dateStr,
        dayNumber,
        inCurrentMonth,
        isToday: dateStr === todayStr,
        events,
        tasks,
        projectMilestones,
        careerMilestones,
    };
}

const calendarGridDays = computed<DayCell[]>(() => {
    const firstOfMonth = new Date(currentYear.value, currentMonth.value, 1);
    const startOffset = firstOfMonth.getDay();
    const gridStart = new Date(
        currentYear.value,
        currentMonth.value,
        1 - startOffset,
    );

    const cells: DayCell[] = [];
    for (let i = 0; i < 42; i++) {
        const d = new Date(
            gridStart.getFullYear(),
            gridStart.getMonth(),
            gridStart.getDate() + i,
        );
        const dateStr = formatYmd(d);
        cells.push(
            buildDayData(
                dateStr,
                d.getDate(),
                d.getMonth() === currentMonth.value,
            ),
        );
    }

    return cells;
});

const selectedDayDetails = computed<DayCell>(() => {
    const match = calendarGridDays.value.find(
        (c) => c.dateStr === selectedDate.value,
    );
    if (match) {
        return match;
    }
    const parsed = new Date(`${selectedDate.value}T00:00:00`);
    return buildDayData(
        selectedDate.value,
        Number.isNaN(parsed.getDate()) ? 1 : parsed.getDate(),
        true,
    );
});

const agendaDates = computed(() => {
    const dateSet = new Set<string>();

    if (showEvents.value) {
        for (const ev of filteredEvents.value) {
            const s = toDateInput(ev.starts_at);
            if (s) {
                dateSet.add(s);
            }
        }
    }
    if (showTasks.value) {
        for (const t of filteredTasks.value) {
            const d = toDateInput(t.due_date);
            if (d) {
                dateSet.add(d);
            }
        }
    }
    if (showProjects.value) {
        for (const p of filteredProjects.value) {
            const s = toDateInput(p.started_at);
            const d = toDateInput(p.due_at);
            if (s) dateSet.add(s);
            if (d) dateSet.add(d);
        }
    }
    if (showCareer.value) {
        for (const c of props.career) {
            const t = toDateInput(c.target_date);
            if (t) dateSet.add(t);
        }
    }

    return Array.from(dateSet)
        .sort()
        .map((dateStr) => {
            const parsed = new Date(`${dateStr}T00:00:00`);
            return buildDayData(dateStr, parsed.getDate(), true);
        });
});

const totalSubtasksCount = computed(() =>
    props.tasks.reduce((sum, t) => sum + (t.subtasks?.length ?? 0), 0),
);

const completedSubtasksTotal = computed(() =>
    props.tasks.reduce(
        (sum, t) => sum + (t.subtasks ?? []).filter((s) => s.completed).length,
        0,
    ),
);

function previousMonth(): void {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value -= 1;
    } else {
        currentMonth.value -= 1;
    }
}

function nextMonth(): void {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value += 1;
    } else {
        currentMonth.value += 1;
    }
}

function goToToday(): void {
    const now = new Date();
    currentYear.value = now.getFullYear();
    currentMonth.value = now.getMonth();
    selectedDate.value = todayStr;
}

function openCreateEvent(dateStr?: string): void {
    editingEvent.value = null;
    if (dateStr) {
        selectedDate.value = dateStr;
    }
    eventDialogOpen.value = true;
}

function openEditEvent(event: CalendarEvent): void {
    editingEvent.value = event;
    eventDialogOpen.value = true;
}

function removeEvent(event: CalendarEvent): void {
    if (!window.confirm(`Delete calendar event "${event.title}"?`)) {
        return;
    }
    router.delete(`/calendar/${event.id}`, { preserveScroll: true });
}

function openCreateTask(options?: {
    dateStr?: string;
    eventId?: number | null;
}): void {
    editingTask.value = null;
    defaultTaskDueDate.value = options?.dateStr ?? selectedDate.value;
    defaultTaskEventId.value = options?.eventId ?? null;
    taskDialogOpen.value = true;
}

function openEditTask(task: Task): void {
    editingTask.value = task;
    taskDialogOpen.value = true;
}

function toggleTask(task: Task): void {
    router.patch(`/tasks/${task.id}/toggle`, {}, { preserveScroll: true });
}

function addQuickTaskToEvent(event: CalendarEvent): void {
    const title = (inlineEventTaskInputs[event.id] ?? '').trim();
    if (!title) {
        return;
    }

    router.post(
        `/calendar/${event.id}/tasks`,
        {
            title,
            due_date: selectedDate.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                inlineEventTaskInputs[event.id] = '';
            },
        },
    );
}

function toggleSubtask(task: Task, subtaskId: string): void {
    router.patch(
        `/tasks/${task.id}/subtasks/${subtaskId}/toggle`,
        {},
        { preserveScroll: true },
    );
}

function addInlineSubtask(task: Task): void {
    const title = (inlineSubtaskInputs[task.id] ?? '').trim();
    if (!title) {
        return;
    }

    router.post(
        `/tasks/${task.id}/subtasks`,
        { title },
        {
            preserveScroll: true,
            onSuccess: () => {
                inlineSubtaskInputs[task.id] = '';
            },
        },
    );
}

function removeSubtask(task: Task, subtaskId: string): void {
    router.delete(`/tasks/${task.id}/subtasks/${subtaskId}`, {
        preserveScroll: true,
    });
}

function completedSubtasksCount(subtasks?: Subtask[] | null): number {
    return (subtasks ?? []).filter((s) => s.completed).length;
}

function subtaskProgress(subtasks?: Subtask[] | null): number {
    const list = subtasks ?? [];
    if (!list.length) {
        return 0;
    }
    return Math.round((completedSubtasksCount(list) / list.length) * 100);
}

function startItemFocusTimer(label: string, minutes = 45): void {
    startDurationTimer({ minutes, label });
}
</script>

<template>
    <Head title="Calendar" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Schedule & Execution"
            title="Interactive Calendar"
            description="Unified schedule connecting your events, tasks inside events, task subtasks, project milestones, and career timeline."
            :icon="CalendarDays"
        >
            <template #actions>
                <Button
                    variant="outline"
                    @click="openCreateTask({ dateStr: selectedDate })"
                >
                    <ListChecks class="size-4" />
                    New task
                </Button>
                <Button @click="openCreateEvent(selectedDate)">
                    <Plus class="size-4" />
                    Schedule event
                </Button>
            </template>
        </PageHeader>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <StatCard
                label="Calendar events"
                :value="events.length"
                :hint="`${tasks.filter((t) => t.calendar_event_id).length} tasks bundled inside events`"
                :icon="CalendarIcon"
                tone="indigo"
            />
            <StatCard
                label="Scheduled tasks"
                :value="tasks.filter((t) => t.due_date).length"
                :hint="`${completedSubtasksTotal}/${totalSubtasksCount} subtasks done`"
                :icon="ListTodo"
                tone="emerald"
            />
            <StatCard
                label="Project milestones"
                :value="projects.filter((p) => p.started_at || p.due_at).length"
                hint="Start & target ship dates"
                :icon="FolderKanban"
                tone="amber"
            />
            <StatCard
                label="Career targets"
                :value="career.length"
                hint="Goals & applications"
                :icon="Target"
                tone="rose"
            />
        </div>

        <!-- Reciprocal Duration Timer + 20-20-20 Live Bar -->
        <section
            :class="[
                'flex flex-col justify-between gap-4 rounded-2xl border p-4 shadow-sm transition-colors lg:flex-row lg:items-center',
                isDurationPausedByEyeBreak
                    ? 'border-amber-500/40 bg-amber-500/5'
                    : 'bg-card',
            ]"
        >
            <div class="flex flex-wrap items-center gap-3">
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-300"
                >
                    <Hourglass class="size-5" />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold">
                            {{
                                durationLabel ||
                                'Focus Duration & 20-20-20 Eye Care Sync'
                            }}
                        </p>
                        <Badge
                            v-if="isDurationPausedByEyeBreak"
                            variant="outline"
                            class="animate-pulse border-amber-500/40 bg-amber-500/15 text-[10px] text-amber-700 dark:text-amber-300"
                        >
                            Duration Auto-Paused by 20-20-20 Break ({{
                                eyeRemainingSeconds
                            }}s)
                        </Badge>
                        <Badge
                            v-else-if="durationRunning"
                            variant="outline"
                            class="border-sky-500/30 bg-sky-500/10 text-[10px] text-sky-700 dark:text-sky-300"
                        >
                            Active · Pauses automatically on 20-20-20 breaks
                        </Badge>
                    </div>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        Start a focus timer on any calendar event or task below.
                        It automatically pauses whenever your 20-20-20 eye break
                        is active.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div
                    class="flex items-center gap-2 rounded-xl border bg-muted/30 px-3 py-1.5 text-xs"
                >
                    <Timer class="size-3.5 text-sky-500" />
                    <span class="text-muted-foreground">Duration:</span>
                    <span class="font-mono font-bold tabular-nums">
                        {{ formattedDurationRemaining }}
                    </span>
                    <span class="text-[11px] text-muted-foreground">
                        (elapsed {{ formattedDurationElapsed }})
                    </span>
                </div>

                <div
                    class="flex items-center gap-2 rounded-xl border bg-muted/30 px-3 py-1.5 text-xs"
                >
                    <Eye
                        :class="[
                            'size-3.5',
                            eyePhase === 'rest'
                                ? 'animate-pulse text-emerald-500'
                                : 'text-indigo-500',
                        ]"
                    />
                    <span class="text-muted-foreground">20-20-20:</span>
                    <span class="font-mono font-bold tabular-nums">
                        {{ eyeFormattedTime }}
                    </span>
                </div>

                <Button
                    size="sm"
                    variant="outline"
                    @click="toggleDurationTimer"
                >
                    <Pause
                        v-if="durationRunning && !isDurationPausedByEyeBreak"
                        class="size-3.5"
                    />
                    <Play v-else class="size-3.5" />
                    {{
                        durationRunning
                            ? 'Pause Duration'
                            : isDurationDefaultState
                              ? 'Start 45m Focus'
                              : 'Resume'
                    }}
                </Button>

                <Button
                    v-if="!isDurationDefaultState"
                    size="sm"
                    variant="ghost"
                    @click="stopDurationTimer"
                >
                    <RotateCcw class="size-3.5" />
                    Reset
                </Button>

                <Button size="sm" variant="ghost" @click="toggleEyeTimer">
                    <Eye class="size-3.5" />
                    {{ eyeRunning ? 'Pause 20-20-20' : 'Start 20-20-20' }}
                </Button>
            </div>
        </section>

        <!-- Toolbar & Stream Filters -->
        <div
            class="flex flex-col justify-between gap-3 rounded-2xl border bg-card p-4 shadow-sm lg:flex-row lg:items-center"
        >
            <div class="flex flex-wrap items-center gap-2">
                <div
                    class="flex items-center rounded-xl border bg-muted/30 p-1"
                >
                    <button
                        type="button"
                        :class="[
                            'rounded-lg px-3 py-1 text-xs font-medium transition',
                            viewMode === 'month'
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="viewMode = 'month'"
                    >
                        Month Grid
                    </button>
                    <button
                        type="button"
                        :class="[
                            'rounded-lg px-3 py-1 text-xs font-medium transition',
                            viewMode === 'agenda'
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="viewMode = 'agenda'"
                    >
                        Agenda Timeline
                    </button>
                </div>

                <div class="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon-sm"
                        aria-label="Previous month"
                        @click="previousMonth"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button variant="outline" size="sm" @click="goToToday">
                        Today
                    </Button>
                    <Button
                        variant="outline"
                        size="icon-sm"
                        aria-label="Next month"
                        @click="nextMonth"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>

                <h2 class="ml-1 text-base font-semibold tracking-tight">
                    {{ monthTitle }}
                </h2>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    :class="[
                        'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-medium transition',
                        showEvents
                            ? 'border-indigo-500/30 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                            : 'text-muted-foreground opacity-60',
                    ]"
                    @click="showEvents = !showEvents"
                >
                    <span class="size-2 rounded-full bg-indigo-500" />
                    Events
                </button>
                <button
                    type="button"
                    :class="[
                        'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-medium transition',
                        showTasks
                            ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                            : 'text-muted-foreground opacity-60',
                    ]"
                    @click="showTasks = !showTasks"
                >
                    <span class="size-2 rounded-full bg-emerald-500" />
                    Tasks &amp; Subtasks
                </button>
                <button
                    type="button"
                    :class="[
                        'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-medium transition',
                        showProjects
                            ? 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300'
                            : 'text-muted-foreground opacity-60',
                    ]"
                    @click="showProjects = !showProjects"
                >
                    <span class="size-2 rounded-full bg-amber-500" />
                    Projects
                </button>
                <button
                    type="button"
                    :class="[
                        'inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-medium transition',
                        showCareer
                            ? 'border-rose-500/30 bg-rose-500/10 text-rose-700 dark:text-rose-300'
                            : 'text-muted-foreground opacity-60',
                    ]"
                    @click="showCareer = !showCareer"
                >
                    <span class="size-2 rounded-full bg-rose-500" />
                    Career
                </button>

                <Select v-model="projectFilter">
                    <SelectTrigger class="h-8 w-44 text-xs">
                        <SelectValue placeholder="All projects" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All projects</SelectItem>
                        <SelectItem
                            v-for="project in projects"
                            :key="project.id"
                            :value="String(project.id)"
                        >
                            {{ project.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Main Calendar Grid + Selected Day Inspector -->
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,0.95fr)]">
            <!-- Left: Month Grid OR Agenda Timeline -->
            <section
                v-if="viewMode === 'month'"
                class="overflow-hidden rounded-2xl border bg-card shadow-sm"
            >
                <div
                    class="grid grid-cols-7 border-b bg-muted/30 text-center text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    <div
                        v-for="day in weekdayHeaders"
                        :key="day"
                        class="py-2.5"
                    >
                        {{ day }}
                    </div>
                </div>

                <div class="grid grid-cols-7 divide-x divide-y">
                    <div
                        v-for="cell in calendarGridDays"
                        :key="cell.dateStr"
                        role="button"
                        tabindex="0"
                        :class="[
                            'group relative flex min-h-28 cursor-pointer flex-col justify-between p-2 text-left transition',
                            cell.inCurrentMonth
                                ? 'bg-card hover:bg-muted/25'
                                : 'bg-muted/15 text-muted-foreground/60 hover:bg-muted/30',
                            selectedDate === cell.dateStr
                                ? 'ring-2 ring-indigo-500 ring-inset'
                                : '',
                        ]"
                        @click="selectedDate = cell.dateStr"
                        @keydown.enter.prevent="selectedDate = cell.dateStr"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                :class="[
                                    'inline-flex size-6 items-center justify-center rounded-full text-xs font-semibold',
                                    cell.isToday
                                        ? 'bg-indigo-600 text-white'
                                        : selectedDate === cell.dateStr
                                          ? 'bg-foreground text-background'
                                          : '',
                                ]"
                            >
                                {{ cell.dayNumber }}
                            </span>

                            <button
                                type="button"
                                class="hidden size-5 items-center justify-center rounded text-muted-foreground transition group-hover:inline-flex hover:bg-muted hover:text-foreground"
                                :aria-label="`Schedule on ${cell.dateStr}`"
                                @click.stop="openCreateEvent(cell.dateStr)"
                            >
                                <Plus class="size-3.5" />
                            </button>
                        </div>

                        <div class="mt-1.5 flex flex-col gap-1">
                            <!-- Events on this day -->
                            <div
                                v-for="ev in cell.events.slice(0, 2)"
                                :key="`ev-${ev.id}`"
                                :class="[
                                    'flex items-center justify-between gap-1 truncate rounded px-1.5 py-0.5 text-[10px] font-medium',
                                    projectColorClasses[ev.color].soft,
                                    projectColorClasses[ev.color].text,
                                ]"
                            >
                                <span class="truncate">{{ ev.title }}</span>
                                <span
                                    v-if="tasksInsideEvent(ev.id).length"
                                    class="shrink-0 font-mono text-[9px] opacity-80"
                                >
                                    {{ tasksInsideEvent(ev.id).length }}t
                                </span>
                            </div>

                            <!-- Tasks due on this day -->
                            <div
                                v-for="task in cell.tasks.slice(0, 2)"
                                :key="`task-${task.id}`"
                                class="flex items-center justify-between gap-1 truncate rounded border bg-muted/40 px-1.5 py-0.5 text-[10px]"
                            >
                                <span
                                    :class="[
                                        'truncate',
                                        task.status === 'done'
                                            ? 'text-muted-foreground line-through'
                                            : 'font-medium',
                                    ]"
                                >
                                    {{ task.title }}
                                </span>
                                <span
                                    v-if="task.subtasks?.length"
                                    class="shrink-0 font-mono text-[9px] text-muted-foreground"
                                >
                                    {{
                                        completedSubtasksCount(task.subtasks)
                                    }}/{{ task.subtasks.length }}
                                </span>
                            </div>

                            <!-- Project milestones -->
                            <div
                                v-for="pm in cell.projectMilestones.slice(0, 1)"
                                :key="pm.id"
                                class="truncate rounded border border-amber-500/30 bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 dark:text-amber-300"
                            >
                                {{ pm.kind === 'start' ? 'Start:' : 'Due:' }}
                                {{ pm.project.name }}
                            </div>

                            <!-- Career milestones -->
                            <div
                                v-for="cm in cell.careerMilestones.slice(0, 1)"
                                :key="`career-${cm.id}`"
                                class="truncate rounded border border-rose-500/30 bg-rose-500/10 px-1.5 py-0.5 text-[10px] font-medium text-rose-700 dark:text-rose-300"
                            >
                                {{ cm.title }}
                            </div>

                            <span
                                v-if="
                                    cell.events.length +
                                        cell.tasks.length +
                                        cell.projectMilestones.length +
                                        cell.careerMilestones.length >
                                    4
                                "
                                class="px-1 text-[10px] font-medium text-muted-foreground"
                            >
                                +{{
                                    cell.events.length +
                                    cell.tasks.length +
                                    cell.projectMilestones.length +
                                    cell.careerMilestones.length -
                                    3
                                }}
                                more
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Agenda View -->
            <section
                v-else
                class="flex flex-col gap-3 rounded-2xl border bg-card p-5 shadow-sm"
            >
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">
                        Chronological Schedule &amp; Milestones
                    </h3>
                    <span class="text-xs text-muted-foreground">
                        {{ agendaDates.length }} scheduled dates
                    </span>
                </div>

                <div v-if="agendaDates.length" class="divide-y">
                    <div
                        v-for="day in agendaDates"
                        :key="day.dateStr"
                        class="py-3.5 first:pt-0 last:pb-0"
                    >
                        <div class="flex items-center justify-between">
                            <button
                                type="button"
                                class="flex items-center gap-2 text-left text-sm font-semibold hover:text-indigo-500"
                                @click="selectedDate = day.dateStr"
                            >
                                <span>{{ formatDate(day.dateStr) }}</span>
                                <Badge
                                    v-if="day.isToday"
                                    variant="default"
                                    class="text-[10px]"
                                >
                                    Today
                                </Badge>
                            </button>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-7 text-xs"
                                @click="selectedDate = day.dateStr"
                            >
                                Inspect day
                            </Button>
                        </div>

                        <div class="mt-2 space-y-1.5">
                            <div
                                v-for="ev in day.events"
                                :key="`ag-ev-${ev.id}`"
                                class="flex items-center justify-between rounded-lg border bg-muted/20 px-3 py-2 text-xs"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="[
                                            'size-2 rounded-full',
                                            projectColorClasses[ev.color].dot,
                                        ]"
                                    />
                                    <span class="font-semibold">{{
                                        ev.title
                                    }}</span>
                                    <Badge
                                        variant="outline"
                                        class="text-[10px]"
                                        :class="
                                            calendarEventTypeClasses[ev.type]
                                        "
                                    >
                                        {{ calendarEventTypeLabel[ev.type] }}
                                    </Badge>
                                </div>
                                <span class="text-[11px] text-muted-foreground">
                                    {{ tasksInsideEvent(ev.id).length }} tasks
                                    inside
                                </span>
                            </div>

                            <div
                                v-for="t in day.tasks"
                                :key="`ag-task-${t.id}`"
                                class="flex items-center justify-between rounded-lg border px-3 py-2 text-xs"
                            >
                                <div class="flex items-center gap-2">
                                    <CheckCircle2
                                        v-if="t.status === 'done'"
                                        class="size-3.5 text-emerald-500"
                                    />
                                    <Circle
                                        v-else
                                        class="size-3.5 text-muted-foreground"
                                    />
                                    <span
                                        :class="
                                            t.status === 'done'
                                                ? 'text-muted-foreground line-through'
                                                : 'font-medium'
                                        "
                                    >
                                        {{ t.title }}
                                    </span>
                                </div>
                                <span
                                    v-if="t.subtasks?.length"
                                    class="text-[11px] text-muted-foreground"
                                >
                                    {{ completedSubtasksCount(t.subtasks) }}/{{
                                        t.subtasks.length
                                    }}
                                    subtasks
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <p
                    v-else
                    class="py-12 text-center text-sm text-muted-foreground"
                >
                    No scheduled events or tasks match your current filters.
                </p>
            </section>

            <!-- Right: Selected Day Inspector & Event/Task/Subtask Workspace -->
            <aside class="flex flex-col gap-4">
                <section class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p
                                class="text-[11px] font-semibold tracking-[0.14em] text-indigo-500 uppercase"
                            >
                                Selected Day Workspace
                            </p>
                            <h3 class="mt-1 text-lg font-semibold">
                                {{ formatDate(selectedDate) }}
                            </h3>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <Button
                                size="sm"
                                variant="outline"
                                class="h-8 text-xs"
                                @click="
                                    openCreateTask({ dateStr: selectedDate })
                                "
                            >
                                <Plus class="size-3.5" />
                                Task
                            </Button>
                            <Button
                                size="sm"
                                class="h-8 text-xs"
                                @click="openCreateEvent(selectedDate)"
                            >
                                <Plus class="size-3.5" />
                                Event
                            </Button>
                        </div>
                    </div>

                    <!-- 1. Events on Selected Day (with Tasks inside Events & Subtasks!) -->
                    <div class="mt-5">
                        <div class="flex items-center justify-between">
                            <h4
                                class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                <Sparkles class="size-3.5 text-indigo-500" />
                                Events &amp; Bundled Tasks ({{
                                    selectedDayDetails.events.length
                                }})
                            </h4>
                        </div>

                        <div
                            v-if="selectedDayDetails.events.length"
                            class="mt-3 space-y-3"
                        >
                            <article
                                v-for="ev in selectedDayDetails.events"
                                :key="ev.id"
                                class="rounded-xl border bg-muted/15 p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-1.5"
                                        >
                                            <span
                                                :class="[
                                                    'size-2 rounded-full',
                                                    projectColorClasses[
                                                        ev.color
                                                    ].dot,
                                                ]"
                                            />
                                            <Badge
                                                variant="outline"
                                                class="text-[10px]"
                                                :class="
                                                    calendarEventTypeClasses[
                                                        ev.type
                                                    ]
                                                "
                                            >
                                                {{
                                                    calendarEventTypeLabel[
                                                        ev.type
                                                    ]
                                                }}
                                            </Badge>
                                            <span
                                                v-if="ev.project"
                                                class="rounded border bg-background px-1.5 py-0.5 text-[10px]"
                                            >
                                                {{ ev.project.name }}
                                            </span>
                                        </div>

                                        <h5
                                            class="mt-1.5 text-sm font-semibold"
                                        >
                                            {{ ev.title }}
                                        </h5>

                                        <div
                                            class="mt-1 flex flex-wrap items-center gap-3 text-[11px] text-muted-foreground"
                                        >
                                            <span
                                                class="flex items-center gap-1"
                                            >
                                                <Clock class="size-3" />
                                                <template v-if="ev.is_all_day">
                                                    All day
                                                </template>
                                                <template
                                                    v-else-if="ev.start_time"
                                                >
                                                    {{ ev.start_time
                                                    }}{{
                                                        ev.end_time
                                                            ? ` – ${ev.end_time}`
                                                            : ''
                                                    }}
                                                </template>
                                                <template v-else>
                                                    Scheduled
                                                </template>
                                            </span>

                                            <span
                                                v-if="ev.location"
                                                class="flex items-center gap-1"
                                            >
                                                <MapPin class="size-3" />
                                                {{ ev.location }}
                                            </span>
                                        </div>

                                        <p
                                            v-if="ev.description"
                                            class="mt-1.5 text-xs text-muted-foreground"
                                        >
                                            {{ ev.description }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex shrink-0 items-center gap-1"
                                    >
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            title="Start 45m Focus Duration Timer for this event"
                                            @click="
                                                startItemFocusTimer(
                                                    `Event: ${ev.title}`,
                                                    45,
                                                )
                                            "
                                        >
                                            <Timer class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            aria-label="Edit event"
                                            @click="openEditEvent(ev)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-rose-500 hover:text-rose-600"
                                            aria-label="Delete event"
                                            @click="removeEvent(ev)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Tasks Inside This Event -->
                                <div
                                    class="mt-3 rounded-xl border bg-background p-3"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <span
                                            class="flex items-center gap-1.5 text-[11px] font-semibold"
                                        >
                                            <ListChecks
                                                class="size-3.5 text-indigo-500"
                                            />
                                            Tasks inside event ({{
                                                tasksInsideEvent(ev.id).length
                                            }})
                                        </span>
                                        <button
                                            type="button"
                                            class="text-[11px] font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                                            @click="
                                                openCreateTask({
                                                    dateStr: selectedDate,
                                                    eventId: ev.id,
                                                })
                                            "
                                        >
                                            + Full task dialog
                                        </button>
                                    </div>

                                    <div
                                        v-if="tasksInsideEvent(ev.id).length"
                                        class="mt-2 space-y-2"
                                    >
                                        <div
                                            v-for="evTask in tasksInsideEvent(
                                                ev.id,
                                            )"
                                            :key="evTask.id"
                                            class="rounded-lg border bg-muted/20 p-2.5 text-xs"
                                        >
                                            <div
                                                class="flex items-start justify-between gap-2"
                                            >
                                                <button
                                                    type="button"
                                                    class="flex min-w-0 flex-1 items-start gap-2 text-left"
                                                    @click="toggleTask(evTask)"
                                                >
                                                    <CheckCircle2
                                                        v-if="
                                                            evTask.status ===
                                                            'done'
                                                        "
                                                        class="mt-0.5 size-4 shrink-0 text-emerald-500"
                                                    />
                                                    <Circle
                                                        v-else
                                                        class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                                    />
                                                    <span
                                                        :class="[
                                                            'font-medium',
                                                            evTask.status ===
                                                            'done'
                                                                ? 'text-muted-foreground line-through'
                                                                : '',
                                                        ]"
                                                    >
                                                        {{ evTask.title }}
                                                    </span>
                                                </button>

                                                <div
                                                    class="flex shrink-0 items-center gap-1"
                                                >
                                                    <button
                                                        type="button"
                                                        class="rounded p-1 text-muted-foreground hover:bg-background hover:text-foreground"
                                                        title="Edit task & subtasks"
                                                        @click="
                                                            openEditTask(evTask)
                                                        "
                                                    >
                                                        <Pencil
                                                            class="size-3"
                                                        />
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Subtasks inside Event's Task -->
                                            <div
                                                v-if="evTask.subtasks?.length"
                                                class="mt-2 space-y-1 border-t pt-2 pl-5"
                                            >
                                                <div
                                                    v-for="sub in evTask.subtasks"
                                                    :key="sub.id"
                                                    class="group/evsub flex items-center justify-between gap-2 text-[11px]"
                                                >
                                                    <button
                                                        type="button"
                                                        class="flex min-w-0 flex-1 items-center gap-1.5 text-left"
                                                        @click="
                                                            toggleSubtask(
                                                                evTask,
                                                                sub.id,
                                                            )
                                                        "
                                                    >
                                                        <CheckCircle2
                                                            v-if="sub.completed"
                                                            class="size-3 shrink-0 text-emerald-500"
                                                        />
                                                        <Circle
                                                            v-else
                                                            class="size-3 shrink-0 text-muted-foreground"
                                                        />
                                                        <span
                                                            :class="
                                                                sub.completed
                                                                    ? 'text-muted-foreground line-through'
                                                                    : ''
                                                            "
                                                        >
                                                            {{ sub.title }}
                                                        </span>
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="opacity-0 transition group-hover/evsub:opacity-100 hover:text-rose-500"
                                                        @click="
                                                            removeSubtask(
                                                                evTask,
                                                                sub.id,
                                                            )
                                                        "
                                                    >
                                                        <X class="size-2.5" />
                                                    </button>
                                                </div>
                                            </div>

                                            <form
                                                class="mt-2 flex items-center gap-1.5 pl-5"
                                                @submit.prevent="
                                                    addInlineSubtask(evTask)
                                                "
                                            >
                                                <Input
                                                    v-model="
                                                        inlineSubtaskInputs[
                                                            evTask.id
                                                        ]
                                                    "
                                                    placeholder="Add subtask to this task..."
                                                    class="h-6 flex-1 text-[11px]"
                                                />
                                                <Button
                                                    type="submit"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="h-6 px-2 text-[10px]"
                                                >
                                                    + Subtask
                                                </Button>
                                            </form>
                                        </div>
                                    </div>

                                    <form
                                        class="mt-2.5 flex items-center gap-1.5"
                                        @submit.prevent="
                                            addQuickTaskToEvent(ev)
                                        "
                                    >
                                        <Input
                                            v-model="
                                                inlineEventTaskInputs[ev.id]
                                            "
                                            placeholder="Add a task inside this event..."
                                            class="h-7 flex-1 text-xs"
                                        />
                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant="secondary"
                                            class="h-7 px-2.5 text-xs"
                                        >
                                            <Plus class="size-3" />
                                            Add Task
                                        </Button>
                                    </form>
                                </div>
                            </article>
                        </div>

                        <p
                            v-else
                            class="mt-2 rounded-xl border border-dashed p-4 text-center text-xs text-muted-foreground"
                        >
                            No events scheduled for this day.
                        </p>
                    </div>

                    <!-- 2. Tasks Due on Selected Day (with Subtasks!) -->
                    <div class="mt-6 border-t pt-5">
                        <div class="flex items-center justify-between">
                            <h4
                                class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                <ListTodo class="size-3.5 text-emerald-500" />
                                Tasks Due on This Day ({{
                                    selectedDayDetails.tasks.length
                                }})
                            </h4>
                            <Link
                                href="/tasks"
                                class="inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                            >
                                Kanban board
                                <ArrowUpRight class="size-3" />
                            </Link>
                        </div>

                        <div
                            v-if="selectedDayDetails.tasks.length"
                            class="mt-3 space-y-2.5"
                        >
                            <div
                                v-for="task in selectedDayDetails.tasks"
                                :key="task.id"
                                class="rounded-xl border bg-muted/15 p-3 text-xs"
                            >
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <button
                                        type="button"
                                        class="flex min-w-0 flex-1 items-start gap-2 text-left"
                                        @click="toggleTask(task)"
                                    >
                                        <CheckCircle2
                                            v-if="task.status === 'done'"
                                            class="mt-0.5 size-4 shrink-0 text-emerald-500"
                                        />
                                        <Circle
                                            v-else
                                            class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p
                                                :class="[
                                                    'font-semibold',
                                                    task.status === 'done'
                                                        ? 'text-muted-foreground line-through'
                                                        : '',
                                                ]"
                                            >
                                                {{ task.title }}
                                            </p>
                                            <div
                                                class="mt-1 flex flex-wrap items-center gap-2"
                                            >
                                                <span
                                                    v-if="task.project"
                                                    class="inline-flex items-center gap-1 rounded border bg-background px-1.5 py-0.5 text-[10px]"
                                                >
                                                    <span
                                                        :class="[
                                                            'size-1.5 rounded-full',
                                                            projectColorClasses[
                                                                task.project
                                                                    .color
                                                            ].dot,
                                                        ]"
                                                    />
                                                    {{ task.project.name }}
                                                </span>
                                                <span
                                                    v-if="task.event"
                                                    class="inline-flex items-center gap-1 rounded border border-indigo-500/30 bg-indigo-500/10 px-1.5 py-0.5 text-[10px] text-indigo-700 dark:text-indigo-300"
                                                >
                                                    {{ task.event.title }}
                                                </span>
                                                <span
                                                    class="inline-flex items-center gap-1 text-[10px] text-muted-foreground capitalize"
                                                >
                                                    <span
                                                        :class="[
                                                            'size-1.5 rounded-full',
                                                            taskPriorityClasses[
                                                                task.priority
                                                            ],
                                                        ]"
                                                    />
                                                    {{ task.priority }}
                                                </span>
                                            </div>
                                        </div>
                                    </button>

                                    <div class="flex items-center gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            title="Start 45m Focus Duration Timer for this task"
                                            @click="
                                                startItemFocusTimer(
                                                    `Task: ${task.title}`,
                                                    45,
                                                )
                                            "
                                        >
                                            <Timer class="size-3.5" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            aria-label="Edit task"
                                            @click="openEditTask(task)"
                                        >
                                            <Pencil class="size-3.5" />
                                        </Button>
                                    </div>
                                </div>

                                <div
                                    v-if="task.subtasks?.length"
                                    class="mt-2.5"
                                >
                                    <ProgressBar
                                        :value="subtaskProgress(task.subtasks)"
                                        color="emerald"
                                    />
                                    <div class="mt-2 space-y-1 pl-4">
                                        <div
                                            v-for="sub in task.subtasks"
                                            :key="sub.id"
                                            class="group/tsub flex items-center justify-between gap-2 text-[11px]"
                                        >
                                            <button
                                                type="button"
                                                class="flex min-w-0 flex-1 items-center gap-1.5 text-left"
                                                @click="
                                                    toggleSubtask(task, sub.id)
                                                "
                                            >
                                                <CheckCircle2
                                                    v-if="sub.completed"
                                                    class="size-3 shrink-0 text-emerald-500"
                                                />
                                                <Circle
                                                    v-else
                                                    class="size-3 shrink-0 text-muted-foreground"
                                                />
                                                <span
                                                    :class="
                                                        sub.completed
                                                            ? 'text-muted-foreground line-through'
                                                            : ''
                                                    "
                                                >
                                                    {{ sub.title }}
                                                </span>
                                            </button>
                                            <button
                                                type="button"
                                                class="opacity-0 transition group-hover/tsub:opacity-100 hover:text-rose-500"
                                                @click="
                                                    removeSubtask(task, sub.id)
                                                "
                                            >
                                                <X class="size-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <form
                                    class="mt-2 flex items-center gap-1.5 pl-4"
                                    @submit.prevent="addInlineSubtask(task)"
                                >
                                    <Input
                                        v-model="inlineSubtaskInputs[task.id]"
                                        placeholder="Add subtask..."
                                        class="h-6 flex-1 text-[11px]"
                                    />
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        size="sm"
                                        class="h-6 px-2 text-[10px]"
                                    >
                                        + Subtask
                                    </Button>
                                </form>
                            </div>
                        </div>

                        <p
                            v-else
                            class="mt-2 rounded-xl border border-dashed p-4 text-center text-xs text-muted-foreground"
                        >
                            No tasks due on this day.
                        </p>
                    </div>

                    <!-- 3. Project & Career Milestones on Selected Day -->
                    <div
                        v-if="
                            selectedDayDetails.projectMilestones.length ||
                            selectedDayDetails.careerMilestones.length
                        "
                        class="mt-6 border-t pt-5"
                    >
                        <h4
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Connected Project &amp; Career Milestones
                        </h4>

                        <div class="mt-2.5 space-y-2">
                            <Link
                                v-for="pm in selectedDayDetails.projectMilestones"
                                :key="pm.id"
                                href="/projects"
                                class="flex items-center justify-between rounded-xl border bg-muted/20 px-3 py-2 text-xs transition hover:bg-muted/40"
                            >
                                <div class="flex items-center gap-2">
                                    <FolderKanban
                                        class="size-3.5 text-amber-500"
                                    />
                                    <span class="font-medium">{{
                                        pm.project.name
                                    }}</span>
                                </div>
                                <Badge variant="outline" class="text-[10px]">
                                    {{
                                        pm.kind === 'start'
                                            ? 'Project Kickoff'
                                            : 'Project Target Due'
                                    }}
                                </Badge>
                            </Link>

                            <Link
                                v-for="cm in selectedDayDetails.careerMilestones"
                                :key="cm.id"
                                href="/career"
                                class="flex items-center justify-between rounded-xl border bg-muted/20 px-3 py-2 text-xs transition hover:bg-muted/40"
                            >
                                <div class="flex items-center gap-2">
                                    <Target class="size-3.5 text-rose-500" />
                                    <span class="font-medium">{{
                                        cm.title
                                    }}</span>
                                </div>
                                <Badge
                                    variant="outline"
                                    class="text-[10px]"
                                    :class="careerTypeClasses[cm.type]"
                                >
                                    {{ careerTypeLabel[cm.type] }}
                                </Badge>
                            </Link>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>

    <EventDialog
        v-model:open="eventDialogOpen"
        :event="editingEvent"
        :projects="projects"
        :tasks="tasks"
        :default-date="selectedDate"
    />

    <TaskDialog
        v-model:open="taskDialogOpen"
        :task="editingTask"
        :projects="projects"
        :events="events"
        :default-due-date="defaultTaskDueDate"
        :default-event-id="defaultTaskEventId"
    />
</template>
