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
import type { CareerEntry, CareerType } from '@/types/portal';

const props = defineProps<{
    entry?: CareerEntry | null;
    defaultType?: CareerType;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    title: '',
    type: 'goal',
    organization: '',
    description: '',
    status: 'planned',
    target_date: '',
});

const isEditing = computed(() => Boolean(props.entry));

function fillForm(): void {
    form.clearErrors();

    if (props.entry) {
        form.title = props.entry.title;
        form.type = props.entry.type;
        form.organization = props.entry.organization ?? '';
        form.description = props.entry.description ?? '';
        form.status = props.entry.status;
        form.target_date = toDateInput(props.entry.target_date);

        return;
    }

    form.reset();
    form.type = props.defaultType ?? 'goal';
    form.status = 'planned';
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

    if (props.entry) {
        form.patch(`/career/${props.entry.id}`, options);
    } else {
        form.post('/career', options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? 'Edit career entry' : 'New career entry' }}
                </DialogTitle>
                <DialogDescription>
                    Track goals, applications, certifications and wins in one
                    private timeline.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-5" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="career-title">Title</Label>
                    <Input
                        id="career-title"
                        v-model="form.title"
                        placeholder="e.g. Land a senior product engineer role"
                        autofocus
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Type</Label>
                        <Select v-model="form.type">
                            <SelectTrigger class="w-full">
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="goal">Goal</SelectItem>
                                <SelectItem value="application">
                                    Application
                                </SelectItem>
                                <SelectItem value="achievement">
                                    Achievement
                                </SelectItem>
                                <SelectItem value="certification">
                                    Certification
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" />
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
                                <SelectItem value="achieved">
                                    Achieved
                                </SelectItem>
                                <SelectItem value="archived"
                                    >Archived</SelectItem
                                >
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="career-organization">Organization</Label>
                        <Input
                            id="career-organization"
                            v-model="form.organization"
                            placeholder="Company or personal"
                        />
                        <InputError :message="form.errors.organization" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="career-target">Target date</Label>
                        <Input
                            id="career-target"
                            v-model="form.target_date"
                            type="date"
                        />
                        <InputError :message="form.errors.target_date" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="career-description">Details</Label>
                    <Textarea
                        id="career-description"
                        v-model="form.description"
                        placeholder="What does success look like?"
                        rows="4"
                    />
                    <InputError :message="form.errors.description" />
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
                        {{ isEditing ? 'Save entry' : 'Add entry' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
