<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, Circle, ListTodo, Plus, X } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
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
import { toDateInput } from '@/lib/format';
import type { CalendarEvent, Project, Subtask, Task } from '@/types/portal';

const props = defineProps<{
    task?: Task | null;
    projects: Pick<Project, 'id' | 'name'>[];
    events?: Pick<CalendarEvent, 'id' | 'title' | 'color' | 'starts_at'>[];
    defaultStatus?: Task['status'];
    defaultProjectId?: number | null;
    defaultEventId?: number | null;
    defaultDueDate?: string | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const newSubtaskTitle = ref('');

const form = useForm({
    title: '',
    description: '',
    project_id: 'none',
    calendar_event_id: 'none',
    status: 'todo',
    priority: 'medium',
    due_date: '',
    subtasks: [] as Subtask[],
});

const isEditing = computed(() => Boolean(props.task));

function createClientId(): string {
    if (
        typeof crypto !== 'undefined' &&
        typeof crypto.randomUUID === 'function'
    ) {
        return crypto.randomUUID();
    }

    return `sub_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
}

function fillForm(): void {
    form.clearErrors();
    newSubtaskTitle.value = '';

    if (props.task) {
        form.title = props.task.title;
        form.description = props.task.description ?? '';
        form.project_id = props.task.project_id
            ? String(props.task.project_id)
            : 'none';
        form.calendar_event_id = props.task.calendar_event_id
            ? String(props.task.calendar_event_id)
            : 'none';
        form.status = props.task.status;
        form.priority = props.task.priority;
        form.due_date = toDateInput(props.task.due_date);
        form.subtasks = (props.task.subtasks ?? []).map((sub) => ({
            id: sub.id || createClientId(),
            title: sub.title,
            completed: Boolean(sub.completed),
        }));

        return;
    }

    form.reset();
    form.status = props.defaultStatus ?? 'todo';
    form.project_id = props.defaultProjectId
        ? String(props.defaultProjectId)
        : 'none';
    form.calendar_event_id = props.defaultEventId
        ? String(props.defaultEventId)
        : 'none';
    form.priority = 'medium';
    form.due_date = props.defaultDueDate
        ? toDateInput(props.defaultDueDate)
        : '';
    form.subtasks = [];
}

watch(open, (isOpen) => {
    if (isOpen) {
        fillForm();
    }
});

function addSubtask(): void {
    const title = newSubtaskTitle.value.trim();

    if (!title) {
        return;
    }

    form.subtasks.push({
        id: createClientId(),
        title,
        completed: false,
    });
    newSubtaskTitle.value = '';
}

function toggleSubtaskItem(index: number): void {
    const item = form.subtasks[index];

    if (item) {
        item.completed = !item.completed;
    }
}

function removeSubtaskItem(index: number): void {
    form.subtasks.splice(index, 1);
}

function submit(): void {
    if (newSubtaskTitle.value.trim()) {
        addSubtask();
    }

    form.transform((data) => ({
        ...data,
        project_id: data.project_id === 'none' ? null : Number(data.project_id),
        calendar_event_id:
            data.calendar_event_id === 'none'
                ? null
                : Number(data.calendar_event_id),
        subtasks: data.subtasks
            .map((sub) => ({
                id: sub.id,
                title: sub.title.trim(),
                completed: Boolean(sub.completed),
            }))
            .filter((sub) => sub.title.length > 0),
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
            newSubtaskTitle.value = '';
        },
    };

    if (props.task) {
        form.patch(`/tasks/${props.task.id}`, options);
    } else {
        form.post('/tasks', options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? 'Edit task' : 'New task' }}
                </DialogTitle>
                <DialogDescription>
                    Capture the next concrete step, break it down into subtasks,
                    and optionally link it to a project or calendar event.
                </DialogDescription>
            </DialogHeader>

            <form
                class="grid gap-5"
                @submit.prevent="submit"
                @keydown.ctrl.enter.prevent="submit"
                @keydown.meta.enter.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="task-title">Task</Label>
                    <Input
                        id="task-title"
                        v-model="form.title"
                        placeholder="e.g. Write the ingestion spec"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="task-description">Notes</Label>
                    <Textarea
                        id="task-description"
                        v-model="form.description"
                        placeholder="Optional context, links or acceptance criteria."
                        rows="2"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label class="flex items-center gap-1.5">
                            <ListTodo class="size-3.5 text-muted-foreground" />
                            Subtasks
                        </Label>
                        <span
                            v-if="form.subtasks.length"
                            class="text-[11px] text-muted-foreground"
                        >
                            {{
                                form.subtasks.filter((s) => s.completed).length
                            }}/{{ form.subtasks.length }} completed
                        </span>
                    </div>

                    <div
                        v-if="form.subtasks.length"
                        class="space-y-1.5 rounded-xl border bg-muted/20 p-2.5"
                    >
                        <div
                            v-for="(subtask, index) in form.subtasks"
                            :key="subtask.id"
                            class="flex items-center gap-2 rounded-lg bg-background px-2.5 py-1.5"
                        >
                            <button
                                type="button"
                                class="shrink-0 text-muted-foreground transition hover:text-emerald-500"
                                @click="toggleSubtaskItem(index)"
                            >
                                <CheckCircle2
                                    v-if="subtask.completed"
                                    class="size-4 text-emerald-500"
                                />
                                <Circle v-else class="size-4" />
                            </button>
                            <Input
                                v-model="subtask.title"
                                class="h-7 flex-1 border-0 bg-transparent px-1 text-xs shadow-none focus-visible:ring-0"
                                :class="
                                    subtask.completed
                                        ? 'text-muted-foreground line-through'
                                        : ''
                                "
                            />
                            <button
                                type="button"
                                class="shrink-0 rounded p-1 text-muted-foreground transition hover:bg-rose-500/10 hover:text-rose-500"
                                aria-label="Remove subtask"
                                @click="removeSubtaskItem(index)"
                            >
                                <X class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Input
                            v-model="newSubtaskTitle"
                            placeholder="Add a subtask step and press Enter..."
                            class="h-8 text-xs"
                            @keydown.enter.prevent="addSubtask"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-8 shrink-0"
                            @click="addSubtask"
                        >
                            <Plus class="size-3.5" />
                            Add
                        </Button>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="task-project">Project</Label>
                        <Select v-model="form.project_id">
                            <SelectTrigger id="task-project" class="w-full">
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

                    <div v-if="events" class="grid gap-2">
                        <Label for="task-event">Calendar Event</Label>
                        <Select v-model="form.calendar_event_id">
                            <SelectTrigger id="task-event" class="w-full">
                                <SelectValue placeholder="No linked event" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none"
                                    >No linked event</SelectItem
                                >
                                <SelectItem
                                    v-for="ev in events"
                                    :key="ev.id"
                                    :value="String(ev.id)"
                                >
                                    {{ ev.title }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.calendar_event_id" />
                    </div>

                    <div v-else class="grid gap-2">
                        <Label for="task-status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="task-status" class="w-full">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todo">To do</SelectItem>
                                <SelectItem value="in_progress">
                                    In progress
                                </SelectItem>
                                <SelectItem value="done">Done</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                </div>

                <div
                    :class="[
                        'grid gap-4',
                        events ? 'sm:grid-cols-3' : 'sm:grid-cols-2',
                    ]"
                >
                    <div v-if="events" class="grid gap-2">
                        <Label for="task-status-with-events">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger
                                id="task-status-with-events"
                                class="w-full"
                            >
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todo">To do</SelectItem>
                                <SelectItem value="in_progress">
                                    In progress
                                </SelectItem>
                                <SelectItem value="done">Done</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="task-priority">Priority</Label>
                        <Select v-model="form.priority">
                            <SelectTrigger id="task-priority" class="w-full">
                                <SelectValue placeholder="Select priority" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="low">Low</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="high">High</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.priority" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="task-due">Due date</Label>
                        <Input
                            id="task-due"
                            v-model="form.due_date"
                            type="date"
                        />
                        <InputError :message="form.errors.due_date" />
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
                        {{ isEditing ? 'Save task' : 'Add task' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
