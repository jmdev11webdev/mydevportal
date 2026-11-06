<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Circle,
    CircleDashed,
    ListChecks,
    LoaderCircle,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    Trash2,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
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
import {
    projectColorClasses,
    taskPriorityClasses,
    taskStatusLabel,
} from '@/lib/colors';
import { isOverdue, relativeDate, toDateInput } from '@/lib/format';
import type { Project, Task, TaskStatus } from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tasks', href: '/tasks' }],
    },
});

const props = defineProps<{
    tasks: Task[];
    projects: Pick<Project, 'id' | 'name' | 'color'>[];
}>();

const dialogOpen = ref(false);
const editing = ref<Task | null>(null);
const defaultStatus = ref<TaskStatus>('todo');
const defaultProjectId = ref<number | null>(null);
const search = ref('');
const projectFilter = ref('all');

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
            (task.project?.name ?? '').toLowerCase().includes(term);
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
            status,
            priority: task.priority,
            due_date: toDateInput(task.due_date) || null,
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
</script>

<template>
    <Head title="Tasks" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Focus"
            title="Tasks"
            description="A clear view of what's next. Move work across the board without losing momentum."
            :icon="ListChecks"
        >
            <template #actions>
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
                    placeholder="Search tasks..."
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
                                </div>
                            </div>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="-mt-1 -mr-1 text-muted-foreground opacity-0 transition group-hover:opacity-100"
                                    >
                                        <MoreHorizontal class="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-44">
                                    <DropdownMenuItem @click="editTask(task)">
                                        <Pencil class="size-4" />
                                        Edit task
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
        :default-status="defaultStatus"
        :default-project-id="defaultProjectId"
    />
</template>
