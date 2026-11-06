<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toDateInput } from '@/lib/format';
import type { Project, Task } from '@/types/portal';

const props = defineProps<{
    task?: Task | null;
    projects: Pick<Project, 'id' | 'name'>[];
    defaultStatus?: Task['status'];
    defaultProjectId?: number | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    title: '',
    description: '',
    project_id: 'none',
    status: 'todo',
    priority: 'medium',
    due_date: '',
});

const isEditing = computed(() => Boolean(props.task));

function fillForm(): void {
    form.clearErrors();

    if (props.task) {
        form.title = props.task.title;
        form.description = props.task.description ?? '';
        form.project_id = props.task.project_id
            ? String(props.task.project_id)
            : 'none';
        form.status = props.task.status;
        form.priority = props.task.priority;
        form.due_date = toDateInput(props.task.due_date);

        return;
    }

    form.reset();
    form.status = props.defaultStatus ?? 'todo';
    form.project_id = props.defaultProjectId
        ? String(props.defaultProjectId)
        : 'none';
    form.priority = 'medium';
}

watch(open, (isOpen) => {
    if (isOpen) {
        fillForm();
    }
});

function submit(): void {
    form.transform((data) => ({
        ...data,
        project_id: data.project_id === 'none' ? null : Number(data.project_id),
    }));

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
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
                    Capture the next concrete step. Small, clear tasks move
                    faster.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="task-title">Task</Label>
                    <Input
                        id="task-title"
                        v-model="form.title"
                        placeholder="e.g. Write the ingestion spec"
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
                        rows="3"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Project</Label>
                        <Select v-model="form.project_id">
                            <SelectTrigger class="w-full">
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
                        <Label>Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger class="w-full">
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

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Priority</Label>
                        <Select v-model="form.priority">
                            <SelectTrigger class="w-full">
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
