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
import { projectColorClasses } from '@/lib/colors';
import { toDateInput } from '@/lib/format';
import type { Project, ProjectColor } from '@/types/portal';

const props = defineProps<{
    project?: Project | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const colors: ProjectColor[] = [
    'indigo',
    'emerald',
    'amber',
    'rose',
    'sky',
    'violet',
];

const form = useForm({
    name: '',
    description: '',
    status: 'active',
    color: 'indigo' as ProjectColor,
    tech_stack: '',
    repository_url: '',
    live_url: '',
    started_at: '',
    due_at: '',
});

const isEditing = computed(() => Boolean(props.project));

function fillForm(): void {
    form.clearErrors();

    if (props.project) {
        form.name = props.project.name;
        form.description = props.project.description ?? '';
        form.status = props.project.status;
        form.color = props.project.color;
        form.tech_stack = (props.project.tech_stack ?? []).join(', ');
        form.repository_url = props.project.repository_url ?? '';
        form.live_url = props.project.live_url ?? '';
        form.started_at = toDateInput(props.project.started_at);
        form.due_at = toDateInput(props.project.due_at);

        return;
    }

    form.reset();
    form.status = 'active';
    form.color = 'indigo';
}

watch(open, (isOpen) => {
    if (isOpen) {
        fillForm();
    }
});

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    };

    if (props.project) {
        form.patch(`/projects/${props.project.id}`, options);
    } else {
        form.post('/projects', options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? 'Edit project' : 'New project' }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Keep the details of this build up to date.'
                            : 'Give your next build a home. You can always refine the details later.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form
                class="grid gap-5"
                @submit.prevent="submit"
                @keydown.ctrl.enter.prevent="submit"
                @keydown.meta.enter.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="project-name">Project name</Label>
                    <Input
                        id="project-name"
                        v-model="form.name"
                        placeholder="e.g. Orbit"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="project-description">Description</Label>
                    <Textarea
                        id="project-description"
                        v-model="form.description"
                        placeholder="What are you building, and why does it matter?"
                        rows="3"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="project-status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="project-status" class="w-full">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="planning"
                                    >Planning</SelectItem
                                >
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="paused">Paused</SelectItem>
                                <SelectItem value="completed"
                                    >Completed</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="project-started">Start date</Label>
                        <Input
                            id="project-started"
                            v-model="form.started_at"
                            type="date"
                        />
                        <InputError :message="form.errors.started_at" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="project-due">Target date</Label>
                        <Input
                            id="project-due"
                            v-model="form.due_at"
                            type="date"
                        />
                        <InputError :message="form.errors.due_at" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>Accent</Label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="color in colors"
                            :key="color"
                            type="button"
                            :aria-label="`Select ${color} accent`"
                            :aria-pressed="form.color === color"
                            :class="[
                                'size-8 rounded-full border-2 transition',
                                projectColorClasses[color].dot,
                                form.color === color
                                    ? 'scale-110 border-foreground'
                                    : 'border-transparent opacity-70 hover:opacity-100',
                            ]"
                            @click="form.color = color"
                        />
                    </div>
                    <InputError :message="form.errors.color" />
                </div>

                <div class="grid gap-2">
                    <Label for="project-stack">Tech stack</Label>
                    <p
                        id="project-stack-hint"
                        class="text-xs text-muted-foreground"
                    >
                        Separate technologies with commas.
                    </p>
                    <Input
                        id="project-stack"
                        v-model="form.tech_stack"
                        placeholder="Vue, Laravel, SQLite"
                        aria-describedby="project-stack-hint"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                    />
                    <InputError :message="form.errors.tech_stack" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="project-repo">Repository URL</Label>
                        <Input
                            id="project-repo"
                            v-model="form.repository_url"
                            placeholder="github.com/you/project"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                        />
                        <InputError :message="form.errors.repository_url" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="project-live">Live URL</Label>
                        <Input
                            id="project-live"
                            v-model="form.live_url"
                            placeholder="example.com"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                        />
                        <InputError :message="form.errors.live_url" />
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
                        {{ isEditing ? 'Save changes' : 'Create project' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
