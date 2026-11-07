<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
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
import { Textarea } from '@/components/ui/textarea';
import { noteColorClasses, noteColorOptions } from '@/lib/colors';
import type { Note, NoteColor } from '@/types/portal';

const props = defineProps<{
    note?: Note | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({
    title: '',
    body: '',
    tags: '',
    color: 'slate' as NoteColor,
    is_pinned: false,
});

const isEditing = computed(() => Boolean(props.note));

function fillForm(): void {
    form.clearErrors();

    if (props.note) {
        form.title = props.note.title;
        form.body = props.note.body;
        form.tags = (props.note.tags ?? []).join(', ');
        form.color = props.note.color;
        form.is_pinned = props.note.is_pinned;

        return;
    }

    form.reset();
    form.color = 'slate';
    form.is_pinned = false;
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

    if (props.note) {
        form.patch(`/notes/${props.note.id}`, options);
    } else {
        form.post('/notes', options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? 'Edit note' : 'New note' }}
                </DialogTitle>
                <DialogDescription>
                    A private scratchpad for snippets, decisions and ideas.
                </DialogDescription>
            </DialogHeader>

            <form
                class="grid gap-5"
                @submit.prevent="submit"
                @keydown.ctrl.enter.prevent="submit"
                @keydown.meta.enter.prevent="submit"
            >
                <div class="grid gap-2">
                    <Label for="note-title">Title</Label>
                    <Input
                        id="note-title"
                        v-model="form.title"
                        placeholder="Give this note a short name"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="note-body">Note</Label>
                    <Textarea
                        id="note-body"
                        v-model="form.body"
                        class="min-h-48 font-mono text-sm"
                        placeholder="Write it down before it disappears..."
                        rows="10"
                        required
                    />
                    <InputError :message="form.errors.body" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="note-tags">Tags</Label>
                        <p
                            id="note-tags-hint"
                            class="text-xs text-muted-foreground"
                        >
                            Separate tags with commas.
                        </p>
                        <Input
                            id="note-tags"
                            v-model="form.tags"
                            placeholder="sqlite, performance"
                            aria-describedby="note-tags-hint"
                            autocorrect="off"
                            autocapitalize="off"
                            spellcheck="false"
                        />
                        <InputError :message="form.errors.tags" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Color</Label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="color in noteColorOptions"
                                :key="color.value"
                                type="button"
                                :aria-label="`${color.label} note color`"
                                :aria-pressed="form.color === color.value"
                                :class="[
                                    'size-8 rounded-lg border-2 transition',
                                    noteColorClasses[color.value].card,
                                    form.color === color.value
                                        ? 'scale-110 border-foreground'
                                        : 'border-transparent opacity-70 hover:opacity-100',
                                ]"
                                @click="form.color = color.value"
                            />
                        </div>
                        <InputError :message="form.errors.color" />
                    </div>
                </div>

                <label
                    class="flex cursor-pointer items-center gap-3 rounded-xl border bg-muted/30 px-4 py-3"
                >
                    <Checkbox v-model="form.is_pinned" />
                    <span class="text-sm">
                        <span class="font-medium">Pin to the top</span>
                        <span class="block text-xs text-muted-foreground">
                            Keep this note in sight on your workspace.
                        </span>
                    </span>
                </label>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditing ? 'Save note' : 'Save note' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
