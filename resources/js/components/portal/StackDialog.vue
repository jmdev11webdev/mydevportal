<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TechStackSelector from '@/components/portal/TechStackSelector.vue';
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
import {
    stackCategories,
    stackCategoryLabel,
    stackStatuses,
    stackStatusLabel,
} from '@/lib/colors';
import type { TechOption } from '@/lib/techStack';
import type { StackCategory, StackItem, StackStatus } from '@/types/portal';

const props = defineProps<{
    item?: StackItem | null;
    defaultCategory?: StackCategory;
}>();

const open = defineModel<boolean>('open', { default: false });

const stackForm = useForm({
    name: '',
    category: 'backend' as StackCategory,
    status: 'core' as StackStatus,
    version: '',
    url: '',
    notes: '',
    is_pinned: false,
});

const isEditing = computed(() => Boolean(props.item));

const selectedTools = ref<string[]>([]);
const lastAutoName = ref('');

function onToolsChange(items: string[]): void {
    selectedTools.value = items;

    const autoName = items.join(', ');
    if (
        !stackForm.name.trim() ||
        stackForm.name.trim() === lastAutoName.value
    ) {
        stackForm.name = autoName;
    }
    lastAutoName.value = autoName;
}

function onSelectTech(option: TechOption): void {
    if (selectedTools.value.length <= 1) {
        stackForm.category = option.category;
    }
}

function fillForm(): void {
    stackForm.clearErrors();

    if (props.item) {
        stackForm.name = props.item.name;
        stackForm.category = props.item.category;
        stackForm.status = props.item.status;
        stackForm.version = props.item.version ?? '';
        stackForm.url = props.item.url ?? '';
        stackForm.notes = props.item.notes ?? '';
        stackForm.is_pinned = props.item.is_pinned;
        selectedTools.value = props.item.name
            .split(',')
            .map((part) => part.trim())
            .filter(Boolean);
        lastAutoName.value = props.item.name;

        return;
    }

    stackForm.reset();
    stackForm.category = props.defaultCategory ?? 'backend';
    stackForm.status = 'core';
    stackForm.is_pinned = false;
    selectedTools.value = [];
    lastAutoName.value = '';
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
        },
    };

    if (props.item) {
        stackForm.patch(`/stack/${props.item.id}`, options);
    } else {
        stackForm.post('/stack', options);
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditing ? 'Edit stack item' : 'Add stack item' }}
                </DialogTitle>
                <DialogDescription>
                    Catalog a language, framework, database, or tool in your
                    personal tech stack.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4 py-2" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="stack-custom-name">Stack / Tool name</Label>
                    <Input
                        id="stack-custom-name"
                        v-model="stackForm.name"
                        placeholder="e.g. TALL Stack, My SaaS Core, Laravel..."
                        required
                    />
                    <InputError :message="stackForm.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="stack-name"
                        >Technologies (optional multi-select)</Label
                    >
                    <TechStackSelector
                        id="stack-name"
                        :model-value="selectedTools"
                        :multiple="true"
                        placeholder="Pick technologies or presets (TALL, MERN, etc.)..."
                        @update:model-value="onToolsChange"
                        @select-option="onSelectTech"
                    />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="stack-category">Category</Label>
                        <Select v-model="stackForm.category">
                            <SelectTrigger id="stack-category" class="w-full">
                                <SelectValue placeholder="Select category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="cat in stackCategories"
                                    :key="cat"
                                    :value="cat"
                                >
                                    {{ stackCategoryLabel[cat] }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="stackForm.errors.category" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="stack-status">Proficiency / Status</Label>
                        <Select v-model="stackForm.status">
                            <SelectTrigger id="stack-status" class="w-full">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="status in stackStatuses"
                                    :key="status"
                                    :value="status"
                                >
                                    {{ stackStatusLabel[status] }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="stackForm.errors.status" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="stack-url">Docs URL (optional)</Label>
                        <Input
                            id="stack-url"
                            v-model="stackForm.url"
                            placeholder="laravel.com/docs"
                        />
                        <InputError :message="stackForm.errors.url" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="stack-version">Version (optional)</Label>
                        <Input
                            id="stack-version"
                            v-model="stackForm.version"
                            placeholder="e.g. 13.x, 3.5, v4"
                        />
                        <InputError :message="stackForm.errors.version" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="stack-notes">Notes (optional)</Label>
                    <Textarea
                        id="stack-notes"
                        v-model="stackForm.notes"
                        rows="3"
                        placeholder="Why you use it, favorite packages, or setup gotchas..."
                    />
                    <InputError :message="stackForm.errors.notes" />
                </div>

                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <Checkbox v-model="stackForm.is_pinned" />
                    <span>Pin to top of stack</span>
                </label>

                <DialogFooter class="gap-2 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="stackForm.processing">
                        {{ isEditing ? 'Save changes' : 'Add to stack' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
