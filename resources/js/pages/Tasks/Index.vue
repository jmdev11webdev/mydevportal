<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckCircle2,
    ChevronDown,
    ChevronRight,
    Circle,
    CircleDashed,
    ListChecks,
    ListTodo,
    LoaderCircle,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    Timer,
    Trash2,
    X,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import TaskDialog from '@/components/portal/TaskDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
    projectColorClasses,
    taskPriorityClasses,
    taskStatusLabel,
} from '@/lib/colors';
import { isOverdue, relativeDate, toDateInput } from '@/lib/format';
import type {
    CalendarEvent,
    Project,
    Subtask,
    Task,
    TaskStatus,
} from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tasks', href: '/tasks' }],
    },
});

const props = defineProps<{
    tasks: Task[];
    projects: Pick<Project, 'id' | 'name' | 'color'>[];
    events?: Pick<CalendarEvent, 'id' | 'title' | 'color' | 'starts_at'>[];
}>();

const { startDurationTimer } = useEyeCareTimer();

const dialogOpen = ref(false);
const editing = ref<Task | null>(null);
const defaultStatus = ref<TaskStatus>('todo');
const defaultProjectId = ref<number | null>(null);
const search = ref('');
const projectFilter = ref('all');

const expandedSubtasks = reactive<Record<number, boolean>>({});
const inlineSubtaskInputs = reactive<Record<number, string>>({});

const columns: { status: TaskStatus; title: string; tone: string }[] = [
    {
        status: 'todo',
        title: 'To do',
        tone: 'text-slate-600 dark:text-slate-300',
    },
    {
        status: 'in_progress',
        title: 'In progress',
        tone: 'text-indigo-600 dark:text-indigo-300',
    },
    {
        status: 'done',
        title: 'Done',
        tone: 'text-emerald-600 dark:text-emerald-300',
    },
];

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.tasks.filter((task) => {
        const matchesSearch =
            !term ||
            task.title.toLowerCase().includes(term) ||
            (task.project?.name ?? '').toLowerCase().includes(term) ||
            (task.event?.title ?? '').toLowerCase().includes(term) ||
            (task.subtasks ?? []).some((s) =>
                s.title.toLowerCase().includes(term),
            );
        const matchesProject =
            projectFilter.value === 'all' ||
            String(task.project_id) === projectFilter.value;

        return matchesSearch && matchesProject;
    });
});

const grouped = computed(() =>
    columns.map((column) => ({
        ...column,
        tasks: filtered.value.filter((task) => task.status === column.status),
    })),
);

function createTask(status: TaskStatus = 'todo'): void {
    editing.value = null;
    defaultStatus.value = status;
    defaultProjectId.value =
        projectFilter.value === 'all' ? null : Number(projectFilter.value);
    dialogOpen.value = true;
}

function editTask(task: Task): void {
    editing.value = task;
    dialogOpen.value = true;
}

function toggleTask(task: Task): void {
    router.patch(`/tasks/${task.id}/toggle`, {}, { preserveScroll: true });
}

function moveTask(task: Task, status: TaskStatus): void {
    if (task.status === status) {
        return;
    }

    router.patch(
        `/tasks/${task.id}`,
        {
            title: task.title,
            description: task.description,
            project_id: task.project_id,
            calendar_event_id: task.calendar_event_id ?? null,
            status,
            priority: task.priority,
            due_date: toDateInput(task.due_date) || null,
            subtasks: task.subtasks ?? [],
        },
        { preserveScroll: true },
    );
}

function removeTask(task: Task): void {
    if (!window.confirm(`Delete "${task.title}"?`)) {
        return;
    }

    router.delete(`/tasks/${task.id}`, { preserveScroll: true });
}

function toggleSubtasksDrawer(taskId: number): void {
    expandedSubtasks[taskId] = !expandedSubtasks[taskId];
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
                expandedSubtasks[task.id] = true;
            },
        },
    );
}

function removeSubtask(task: Task, subtaskId: string): void {
    router.delete(`/tasks/${task.id}/subtasks/${subtaskId}`, {
        preserveScroll: true,
    });
}

function startTaskFocusTimer(task: Task, minutes = 45): void {
    startDurationTimer({
        minutes,
        label: `Task: ${task.title}`,
    });
}
</script>

<template>
    <Head title="Tasks" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Focus"
            title="Tasks"
            description="A clear view of what's next. Break work into subtasks, connect to calendar events, and move across the board."
            :icon="ListChecks"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link href="/calendar">
                        <CalendarDays class="size-4" />
                        Open Calendar
                    </Link>
                </Button>
                <Button @click="createTask()">
                    <Plus class="size-4" />
                    New task
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search tasks, subtasks, events..."
                />
            </div>
            <Select v-model="projectFilter">
                <SelectTrigger class="w-full sm:w-52">
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
            <div class="text-xs text-muted-foreground sm:ml-auto">
                {{ filtered.length }} task{{ filtered.length === 1 ? '' : 's' }}
                in view
            </div>
        </div>

        <div v-if="tasks.length" class="grid gap-4 lg:grid-cols-3">
            <section
                v-for="column in grouped"
                :key="column.status"
                class="flex flex-col rounded-2xl border bg-muted/20 p-3"
            >
                <header class="flex items-center justify-between px-1 py-2">
                    <div class="flex items-center gap-2">
                        <span :class="['text-sm font-semibold', column.tone]">{{
                            column.title
                        }}</span>
                        <Badge variant="secondary" class="rounded-full">
                            {{ column.tasks.length }}
                        </Badge>
                    </div>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="text-muted-foreground"
                        :aria-label="`Add task to ${column.title}`"
                        @click="createTask(column.status)"
                    >
                        <Plus class="size-4" />
                    </Button>
                </header>

                <div class="flex flex-col gap-2">
                    <article
                        v-for="task in column.tasks"
                        :key="task.id"
                        class="group rounded-xl border bg-card p-3 shadow-sm transition hover:border-foreground/20 hover:shadow"
                    >
                        <div class="flex items-start gap-2.5">
                            <button
                                type="button"
                                class="mt-0.5 shrink-0 text-muted-foreground transition hover:text-emerald-500"
                                :aria-label="
                                    task.status === 'done'
                                        ? 'Reopen task'
                                        : 'Complete task'
                                "
                                @click="toggleTask(task)"
                            >
                                <CheckCircle2
                                    v-if="task.status === 'done'"
                                    class="size-5 text-emerald-500"
                                />
                                <Circle v-else class="size-5" />
                            </button>

                            <div class="min-w-0 flex-1">
                                <p
                                    :class="[
                                        'text-sm leading-snug font-medium',
                                        task.status === 'done'
                                            ? 'text-muted-foreground line-through'
                                            : '',
                                    ]"
                                >
                                    {{ task.title }}
                                </p>

                                <p
                                    v-if="task.description"
                                    class="mt-1 line-clamp-2 text-xs text-muted-foreground"
                                >
                                    {{ task.description }}
                                </p>

                                <div
                                    class="mt-2 flex flex-wrap items-center gap-2"
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

                                    <Link
                                        v-if="task.event"
                                        href="/calendar"
                                        class="flex items-center gap-1 rounded-md border border-indigo-500/30 bg-indigo-500/10 px-1.5 py-0.5 text-[11px] text-indigo-700 transition hover:bg-indigo-500/20 dark:text-indigo-300"
                                    >
                                        <CalendarDays class="size-3" />
                                        {{ task.event.title }}
                                    </Link>

                                    <span
                                        class="flex items-center gap-1.5 text-[11px] text-muted-foreground"
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

                                    <span
                                        v-if="task.due_date"
                                        :class="[
                                            'text-[11px]',
                                            isOverdue(task.due_date) &&
                                            task.status !== 'done'
                                                ? 'font-medium text-rose-500'
                                                : 'text-muted-foreground',
                                        ]"
                                    >
                                        {{ relativeDate(task.due_date) }}
                                    </span>

                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-md border bg-muted/30 px-1.5 py-0.5 text-[11px] text-muted-foreground transition hover:bg-muted hover:text-foreground"
                                        @click="toggleSubtasksDrawer(task.id)"
                                    >
                                        <ListTodo class="size-3" />
                                        <span v-if="task.subtasks?.length">
                                            {{
                                                completedSubtasksCount(
                                                    task.subtasks,
                                                )
                                            }}/{{ task.subtasks.length }}
                                        </span>
                                        <span v-else>Subtasks</span>
                                        <ChevronDown
                                            v-if="expandedSubtasks[task.id]"
                                            class="size-3"
                                        />
                                        <ChevronRight v-else class="size-3" />
                                    </button>
                                </div>

                                <div v-if="task.subtasks?.length" class="mt-2">
                                    <ProgressBar
                                        :value="subtaskProgress(task.subtasks)"
                                        color="emerald"
                                    />
                                </div>

                                <!-- Subtasks Interactive Checklist -->
                                <div
                                    v-if="
                                        expandedSubtasks[task.id] ||
                                        (task.subtasks &&
                                            task.subtasks.length > 0)
                                    "
                                    class="mt-2.5 space-y-1.5 rounded-lg border bg-muted/20 p-2"
                                >
                                    <div
                                        v-for="subtask in task.subtasks ?? []"
                                        :key="subtask.id"
                                        class="group/sub flex items-center justify-between gap-2 rounded px-1.5 py-1 text-xs transition hover:bg-background"
                                    >
                                        <button
                                            type="button"
                                            class="flex min-w-0 flex-1 items-center gap-2 text-left"
                                            @click="
                                                toggleSubtask(task, subtask.id)
                                            "
                                        >
                                            <CheckCircle2
                                                v-if="subtask.completed"
                                                class="size-3.5 shrink-0 text-emerald-500"
                                            />
                                            <Circle
                                                v-else
                                                class="size-3.5 shrink-0 text-muted-foreground"
                                            />
                                            <span
                                                :class="[
                                                    'truncate',
                                                    subtask.completed
                                                        ? 'text-muted-foreground line-through'
                                                        : '',
                                                ]"
                                            >
                                                {{ subtask.title }}
                                            </span>
                                        </button>
                                        <button
                                            type="button"
                                            class="opacity-0 transition group-hover/sub:opacity-100 hover:text-rose-500"
                                            aria-label="Remove subtask"
                                            @click="
                                                removeSubtask(task, subtask.id)
                                            "
                                        >
                                            <X class="size-3" />
                                        </button>
                                    </div>

                                    <form
                                        v-if="expandedSubtasks[task.id]"
                                        class="flex items-center gap-1.5 pt-1"
                                        @submit.prevent="addInlineSubtask(task)"
                                    >
                                        <Input
                                            v-model="
                                                inlineSubtaskInputs[task.id]
                                            "
                                            placeholder="Add subtask..."
                                            class="h-7 flex-1 text-xs"
                                        />
                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant="secondary"
                                            class="h-7 px-2 text-[11px]"
                                        >
                                            <Plus class="size-3" />
                                            Add
                                        </Button>
                                    </form>
                                </div>
                            </div>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="-mt-1 -mr-1 text-muted-foreground transition focus-visible:opacity-100 data-[state=open]:opacity-100 sm:opacity-0 sm:group-hover:opacity-100"
                                        :aria-label="`Actions for ${task.title}`"
                                    >
                                        <MoreHorizontal class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-48">
                                    <DropdownMenuItem @click="editTask(task)">
                                        <Pencil class="size-4" />
                                        Edit task &amp; subtasks
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        @click="startTaskFocusTimer(task, 45)"
                                    >
                                        <Timer class="size-4" />
                                        Start 45m Focus Timer
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuLabel
                                        >Move to</DropdownMenuLabel
                                    >
                                    <DropdownMenuItem
                                        v-for="target in columns"
                                        :key="target.status"
                                        :disabled="
                                            target.status === task.status
                                        "
                                        @click="moveTask(task, target.status)"
                                    >
                                        <CircleDashed
                                            v-if="target.status === 'todo'"
                                            class="size-4"
                                        />
                                        <LoaderCircle
                                            v-else-if="
                                                target.status === 'in_progress'
                                            "
                                            class="size-4"
                                        />
                                        <CheckCircle2 v-else class="size-4" />
                                        {{ taskStatusLabel[target.status] }}
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        variant="destructive"
                                        @click="removeTask(task)"
                                    >
                                        <Trash2 class="size-4" />
                                        Delete task
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </article>

                    <button
                        v-if="!column.tasks.length"
                        type="button"
                        class="flex items-center justify-center gap-1.5 rounded-xl border border-dashed py-8 text-xs text-muted-foreground transition hover:border-foreground/30 hover:text-foreground"
                        @click="createTask(column.status)"
                    >
                        <Plus class="size-3.5" />
                        Add to {{ column.title.toLowerCase() }}
                    </button>
                </div>
            </section>
        </div>

        <EmptyState
            v-else
            :icon="ListChecks"
            title="Your board is clear"
            description="Add the first task and start moving it across the board."
        >
            <Button @click="createTask()">
                <Plus class="size-4" />
                New task
            </Button>
        </EmptyState>
    </div>

    <TaskDialog
        v-model:open="dialogOpen"
        :task="editing"
        :projects="projects"
        :events="events"
        :default-status="defaultStatus"
        :default-project-id="defaultProjectId"
    />
</template>
