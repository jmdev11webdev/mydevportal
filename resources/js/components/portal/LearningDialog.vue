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
import type { LearningResource } from '@/types/portal';

const props = defineProps<{
    resource?: LearningResource | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    title: '',
    provider: '',
    url: '',
    category: 'General',
    format: 'course',
    status: 'planned',
    progressPercent: 0,
    notes: '',
});

const isEditing = computed(() => Boolean(props.resource));

function fillForm(): void {
    form.clearErrors();

    if (props.resource) {
        form.title = props.resource.title;
        form.provider = props.resource.provider ?? '';
        form.url = props.resource.url ?? '';
        form.category = props.resource.category;
        form.format = props.resource.format;
        form.status = props.resource.status;
        form.progressPercent = props.resource.progress;
        form.notes = props.resource.notes ?? '';

        return;
    }

    form.reset();
    form.category = 'General';
    form.format = 'course';
    form.status = 'planned';
    form.progressPercent = 0;
}

watch(open, (isOpen) => {
    if (isOpen) {
        fillForm();
    }
});

watch(
    () => form.progressPercent,
    (progress) => {
        if (progress >= 100) {
            form.status = 'completed';
        } else if (progress > 0 && form.status === 'planned') {
            form.status = 'in_progress';
        }
    },
);

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    };

    form.transform((data) => {
        const { progressPercent, ...rest } = data;

        return { ...rest, progress: progressPercent };
    });

    if (props.resource) {
        form.patch(`/learning/${props.resource.id}`, options);
    } else {
        form.post('/learning', options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? 'Edit resource' : 'New learning resource' }}
                </DialogTitle>
                <DialogDescription>
                    Track courses, books and docs you actually want to finish.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="learning-title">Title</Label>
                    <Input
                        id="learning-title"
                        v-model="form.title"
                        placeholder="e.g. Designing Data-Intensive Applications"
                        autofocus
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="learning-provider">Provider</Label>
                        <Input
                            id="learning-provider"
                            v-model="form.provider"
                            placeholder="Laracasts, O'Reilly..."
                        />
                        <InputError :message="form.errors.provider" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="learning-category">Category</Label>
                        <Input
                            id="learning-category"
                            v-model="form.category"
                            placeholder="Backend"
                        />
                        <InputError :message="form.errors.category" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="learning-url">URL</Label>
                    <Input
                        id="learning-url"
                        v-model="form.url"
                        placeholder="example.com/resource"
                    />
                    <InputError :message="form.errors.url" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Format</Label>
                        <Select v-model="form.format">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select format" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="course">Course</SelectItem>
                                <SelectItem value="book">Book</SelectItem>
                                <SelectItem value="article">Article</SelectItem>
                                <SelectItem value="video">Video</SelectItem>
                                <SelectItem value="docs">Docs</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.format" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="planned">Planned</SelectItem>
                                <SelectItem value="in_progress">
                                    In progress
                                </SelectItem>
                                <SelectItem value="completed">
                                    Completed
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="learning-progress">Progress</Label>
                        <span class="text-sm font-semibold">
                            {{ form.progressPercent }}%
                        </span>
                    </div>
                    <input
                        id="learning-progress"
                        v-model.number="form.progressPercent"
                        type="range"
                        min="0"
                        max="100"
                        step="5"
                        class="h-2 w-full cursor-pointer appearance-none rounded-full bg-muted accent-indigo-500"
                    />
                    <InputError :message="form.errors.progressPercent" />
                </div>

                <div class="grid gap-2">
                    <Label for="learning-notes">Notes</Label>
                    <Textarea
                        id="learning-notes"
                        v-model="form.notes"
                        placeholder="Key takeaways, chapters to revisit..."
                        rows="3"
                    />
                    <InputError :message="form.errors.notes" />
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
                        {{ isEditing ? 'Save resource' : 'Add resource' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
