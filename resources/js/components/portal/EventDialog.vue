<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    CheckSquare,
    ListChecks,
    ListTodo,
    Plus,
    X,
} from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { projectColorClasses } from '@/lib/colors';
import { toDateInput, todayInput } from '@/lib/format';
import type {
    CalendarEvent,
    CalendarEventType,
    Project,
    ProjectColor,
    Task,
    TaskPriority,
} from '@/types/portal';

const props = defineProps<{
    event?: CalendarEvent | null;
    projects: Pick<Project, 'id' | 'name' | 'color'>[];
    tasks: Task[];
    defaultDate?: string | null;
    defaultProjectId?: number | null;
}>();

const open = defineModel<boolean>('open', { default: false });

type DraftEventTask = {
    title: string;
    priority: TaskPriority;
    subtasks: { title: string }[];
};

const newTaskTitle = ref('');
const newTaskPriority = ref<TaskPriority>('medium');
const newTaskSubtasksInput = ref('');

const colorOptions: { value: ProjectColor; label: string }[] = [
    { value: 'indigo', label: 'Indigo' },
    { value: 'emerald', label: 'Emerald' },
    { value: 'amber', label: 'Amber' },
    { value: 'rose', label: 'Rose' },
    { value: 'sky', label: 'Sky' },
    { value: 'violet', label: 'Violet' },
];

const typeOptions: { value: CalendarEventType; label: string }[] = [
    { value: 'sprint', label: 'Sprint' },
    { value: 'meeting', label: 'Meeting' },
    { value: 'release', label: 'Release' },
    { value: 'deep_work', label: 'Deep work' },
    { value: 'deadline', label: 'Deadline' },
    { value: 'personal', label: 'Personal' },
];

const form = useForm({
    title: '',
    description: '',
    project_id: 'none',
    type: 'sprint' as CalendarEventType,
    color: 'indigo' as ProjectColor,
    starts_at: todayInput(),
    ends_at: '',
    start_time: '09:00',
    end_time: '10:00',
    location: '',
    is_all_day: false,
    task_ids: [] as number[],
    new_tasks: [] as DraftEventTask[],
});

const isEditing = computed(() => Boolean(props.event));

function fillForm(): void {
    form.clearErrors();
    newTaskTitle.value = '';
    newTaskPriority.value = 'medium';
    newTaskSubtasksInput.value = '';

    if (props.event) {
        form.title = props.event.title;
        form.description = props.event.description ?? '';
        form.project_id = props.event.project_id
            ? String(props.event.project_id)
            : 'none';
        form.type = props.event.type;
        form.color = props.event.color;
        form.starts_at = toDateInput(props.event.starts_at) || todayInput();
        form.ends_at = toDateInput(props.event.ends_at);
        form.start_time = props.event.start_time ?? '09:00';
        form.end_time = props.event.end_time ?? '10:00';
        form.location = props.event.location ?? '';
        form.is_all_day = Boolean(props.event.is_all_day);
        form.task_ids = props.tasks
            .filter((t) => t.calendar_event_id === props.event?.id)
            .map((t) => t.id);
        form.new_tasks = [];

        return;
    }

    form.reset();
    form.type = 'sprint';
    form.color = 'indigo';
    form.starts_at = props.defaultDate
        ? toDateInput(props.defaultDate)
        : todayInput();
    form.ends_at = '';
    form.start_time = '09:00';
    form.end_time = '10:00';
    form.is_all_day = false;
    form.project_id = props.defaultProjectId
        ? String(props.defaultProjectId)
        : 'none';
    form.task_ids = [];
    form.new_tasks = [];
}

watch(open, (isOpen) => {
    if (isOpen) {
        fillForm();
    }
});

function toggleAttachedTask(taskId: number): void {
    const idx = form.task_ids.indexOf(taskId);
    if (idx >= 0) {
        form.task_ids.splice(idx, 1);
    } else {
        form.task_ids.push(taskId);
    }
}

function addDraftTask(): void {
    const title = newTaskTitle.value.trim();
    if (!title) {
        return;
    }

    const subtasks = newTaskSubtasksInput.value
        .split(',')
        .map((s) => s.trim())
        .filter((s) => s.length > 0)
        .map((s) => ({ title: s }));

    form.new_tasks.push({
        title,
        priority: newTaskPriority.value,
        subtasks,
    });

    newTaskTitle.value = '';
    newTaskPriority.value = 'medium';
    newTaskSubtasksInput.value = '';
}

function removeDraftTask(index: number): void {
    form.new_tasks.splice(index, 1);
}

function submit(): void {
    if (newTaskTitle.value.trim()) {
        addDraftTask();
    }

    form.transform((data) => ({
        ...data,
        project_id: data.project_id === 'none' ? null : Number(data.project_id),
        ends_at: data.ends_at ? data.ends_at : null,
        start_time: data.is_all_day ? null : data.start_time || null,
        end_time: data.is_all_day ? null : data.end_time || null,
        location: data.location.trim() || null,
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    };

    if (props.event) {
        form.patch(`/calendar/${props.event.id}`, options);
    } else {
        form.post('/calendar', options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[92vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <CalendarDays class="size-5 text-indigo-500" />
                    {{
                        isEditing
                            ? 'Edit calendar event'
                            : 'Schedule calendar event'
                    }}
                </DialogTitle>
                <DialogDescription>
                    Schedule sprints, releases, deep work blocks or meetings —
                    and bundle tasks &amp; subtasks directly inside the event.
                </DialogDescription>
            </DialogHeader>

            <form
                class="grid gap-5"
                @submit.prevent="submit"
                @keydown.ctrl.enter.prevent="submit"
                @keydown.meta.enter.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="event-title">Event title</Label>
                    <Input
                        id="event-title"
                        v-model="form.title"
                        placeholder="e.g. Orbit v1.0 Sprint Planning & Architecture Review"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="event-type">Event type</Label>
                        <Select v-model="form.type">
                            <SelectTrigger id="event-type" class="w-full">
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="item in typeOptions"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="event-project">Connected project</Label>
                        <Select v-model="form.project_id">
                            <SelectTrigger id="event-project" class="w-full">
                                <SelectValue placeholder="No project" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No project</SelectItem>
                                <SelectItem
                                    v-for="project in projects"
                                    :key="project.id"
                                    :value="String(project.id)"
                                >
                                    {{ project.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.project_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Color accent</Label>
                        <div
                            class="flex h-9 items-center gap-2 rounded-md border px-3"
                        >
                            <button
                                v-for="option in colorOptions"
                                :key="option.value"
                                type="button"
                                :aria-label="option.label"
                                :class="[
                                    'size-5 rounded-full transition',
                                    projectColorClasses[option.value].dot,
                                    form.color === option.value
                                        ? 'ring-2 ring-foreground ring-offset-2 ring-offset-background'
                                        : 'opacity-55 hover:opacity-100',
                                ]"
                                @click="form.color = option.value"
                            />
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="event-starts">Start date</Label>
                        <Input
                            id="event-starts"
                            v-model="form.starts_at"
                            type="date"
                            required
                        />
                        <InputError :message="form.errors.starts_at" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="event-ends">End date (optional)</Label>
                        <Input
                            id="event-ends"
                            v-model="form.ends_at"
                            type="date"
                            :min="form.starts_at"
                        />
                        <InputError :message="form.errors.ends_at" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3 sm:items-end">
                    <label
                        for="event-all-day"
                        class="flex h-9 cursor-pointer items-center gap-2 rounded-md border px-3 text-xs font-medium"
                    >
                        <Checkbox
                            id="event-all-day"
                            v-model="form.is_all_day"
                        />
                        <span>All-day event</span>
                    </label>

                    <div v-if="!form.is_all_day" class="grid gap-2">
                        <Label for="event-start-time">Start time</Label>
                        <Input
                            id="event-start-time"
                            v-model="form.start_time"
                            type="time"
                        />
                    </div>

                    <div v-if="!form.is_all_day" class="grid gap-2">
                        <Label for="event-end-time">End time</Label>
                        <Input
                            id="event-end-time"
                            v-model="form.end_time"
                            type="time"
                        />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="event-location">Location / URL</Label>
                        <Input
                            id="event-location"
                            v-model="form.location"
                            placeholder="e.g. Zoom, Discord, Room 4B, or PR link"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="event-description">Agenda / notes</Label>
                        <Textarea
                            id="event-description"
                            v-model="form.description"
                            placeholder="Goals, agenda items, or release checklist."
                            rows="2"
                        />
                    </div>
                </div>

                <div class="rounded-xl border bg-muted/15 p-4">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <ListChecks class="size-4 text-indigo-500" />
                            <h3
                                class="text-xs font-semibold tracking-wider uppercase"
                            >
                                Tasks Inside This Event
                            </h3>
                        </div>
                        <Badge variant="outline" class="text-[10px]">
                            {{ form.task_ids.length + form.new_tasks.length }}
                            task{{
                                form.task_ids.length + form.new_tasks.length ===
                                1
                                    ? ''
                                    : 's'
                            }}
                            linked
                        </Badge>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Create new tasks (with optional subtasks) directly
                        inside this event, or attach existing workspace tasks.
                    </p>

                    <div v-if="form.new_tasks.length" class="mt-3 space-y-2">
                        <div
                            v-for="(draftTask, index) in form.new_tasks"
                            :key="index"
                            class="flex items-start justify-between gap-2 rounded-lg border bg-background p-2.5 text-xs"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <Badge
                                        variant="secondary"
                                        class="text-[10px] capitalize"
                                    >
                                        New · {{ draftTask.priority }}
                                    </Badge>
                                    <span class="font-medium">{{
                                        draftTask.title
                                    }}</span>
                                </div>
                                <div
                                    v-if="draftTask.subtasks.length"
                                    class="mt-1.5 flex flex-wrap gap-1"
                                >
                                    <span
                                        v-for="(
                                            sub, sIdx
                                        ) in draftTask.subtasks"
                                        :key="sIdx"
                                        class="inline-flex items-center gap-1 rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground"
                                    >
                                        <ListTodo class="size-2.5" />
                                        {{ sub.title }}
                                    </span>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="rounded p-1 text-muted-foreground transition hover:bg-rose-500/10 hover:text-rose-500"
                                @click="removeDraftTask(index)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    <div
                        class="mt-3 grid gap-2 rounded-xl border bg-background p-3"
                    >
                        <p class="text-[11px] font-medium">
                            Quick-create a task inside this event
                        </p>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <Input
                                v-model="newTaskTitle"
                                placeholder="Task title (e.g. Run migration dry-run)..."
                                class="h-8 flex-1 text-xs"
                                @keydown.enter.prevent="addDraftTask"
                            />
                            <select
                                v-model="newTaskPriority"
                                class="h-8 rounded-md border border-input bg-transparent px-2 text-xs"
                            >
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <Input
                                v-model="newTaskSubtasksInput"
                                placeholder="Optional subtasks (comma-separated, e.g. Backup DB, Verify indexes)..."
                                class="h-8 flex-1 text-xs"
                                @keydown.enter.prevent="addDraftTask"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-8 shrink-0"
                                @click="addDraftTask"
                            >
                                <Plus class="size-3.5" />
                                Add to event
                            </Button>
                        </div>
                    </div>

                    <div v-if="tasks.length" class="mt-3">
                        <p
                            class="mb-1.5 flex items-center gap-1.5 text-[11px] font-medium text-muted-foreground"
                        >
                            <CheckSquare class="size-3.5" />
                            Or attach existing workspace tasks:
                        </p>
                        <div
                            class="max-h-36 space-y-1 overflow-y-auto rounded-lg border bg-background p-2"
                        >
                            <label
                                v-for="task in tasks"
                                :key="task.id"
                                class="flex cursor-pointer items-center justify-between gap-2 rounded-md px-2 py-1.5 text-xs transition hover:bg-muted/50"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <input
                                        type="checkbox"
                                        class="size-3.5 rounded border-input"
                                        :checked="
                                            form.task_ids.includes(task.id)
                                        "
                                        @change="toggleAttachedTask(task.id)"
                                    />
                                    <span
                                        class="truncate"
                                        :class="
                                            task.status === 'done'
                                                ? 'text-muted-foreground line-through'
                                                : ''
                                        "
                                    >
                                        {{ task.title }}
                                    </span>
                                </div>
                                <span
                                    v-if="task.subtasks?.length"
                                    class="shrink-0 text-[10px] text-muted-foreground"
                                >
                                    {{
                                        task.subtasks.filter((s) => s.completed)
                                            .length
                                    }}/{{ task.subtasks.length }} subtasks
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Save event' : 'Schedule event' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
