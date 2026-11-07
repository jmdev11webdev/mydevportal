<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    Lock,
    MoreHorizontal,
    NotebookPen,
    Pencil,
    Pin,
    PinOff,
    Plus,
    Search,
    Trash2,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import NoteDialog from '@/components/portal/NoteDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { noteColorClasses } from '@/lib/colors';
import { formatDate } from '@/lib/format';
import type { Note } from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Notes', href: '/notes' }],
    },
});

const props = defineProps<{
    notes: Note[];
}>();

const dialogOpen = ref(false);
const editing = ref<Note | null>(null);
const search = ref('');

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();

    if (!term) {
        return props.notes;
    }

    return props.notes.filter(
        (note) =>
            note.title.toLowerCase().includes(term) ||
            note.body.toLowerCase().includes(term) ||
            (note.tags ?? []).some((tag) => tag.toLowerCase().includes(term)),
    );
});

const pinnedCount = computed(
    () => props.notes.filter((note) => note.is_pinned).length,
);

function createNote(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function editNote(note: Note): void {
    editing.value = note;
    dialogOpen.value = true;
}

function togglePin(note: Note): void {
    router.patch(`/notes/${note.id}/pin`, {}, { preserveScroll: true });
}

function removeNote(note: Note): void {
    if (!window.confirm(`Delete "${note.title}"?`)) {
        return;
    }

    router.delete(`/notes/${note.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Notes" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Private workspace"
            title="Notes"
            description="Snippets, decisions and half-formed ideas. Stored for your eyes only."
            :icon="NotebookPen"
        >
            <template #actions>
                <Badge
                    variant="outline"
                    class="hidden gap-1.5 border-emerald-500/30 bg-emerald-500/10 text-emerald-700 sm:flex dark:text-emerald-300"
                >
                    <Lock class="size-3" />
                    Private by default
                </Badge>
                <Button @click="createNote">
                    <Plus class="size-4" />
                    New note
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1 sm:max-w-sm">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Search notes, tags, snippets..."
                />
            </div>
            <div class="text-xs text-muted-foreground sm:ml-auto">
                {{ notes.length }} note{{ notes.length === 1 ? '' : 's' }} ·
                {{ pinnedCount }} pinned
            </div>
        </div>

        <div
            v-if="filtered.length"
            class="columns-1 gap-4 md:columns-2 xl:columns-3"
        >
            <article
                v-for="note in filtered"
                :key="note.id"
                :class="[
                    'group relative mb-4 break-inside-avoid rounded-2xl border p-5 shadow-sm transition hover:shadow-md',
                    noteColorClasses[note.color].card,
                ]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <Pin
                                v-if="note.is_pinned"
                                class="size-3.5 shrink-0 text-foreground/60"
                            />
                            <h2 class="leading-tight font-semibold">
                                {{ note.title }}
                            </h2>
                        </div>
                        <p
                            class="mt-1 text-[11px] tracking-wide text-muted-foreground uppercase"
                        >
                            {{ formatDate(note.updated_at) }}
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="text-muted-foreground"
                            :aria-label="
                                note.is_pinned ? 'Unpin note' : 'Pin note'
                            "
                            @click="togglePin(note)"
                        >
                            <PinOff v-if="note.is_pinned" class="size-3.5" />
                            <Pin v-else class="size-3.5" />
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-muted-foreground"
                                    :aria-label="`Actions for ${note.title}`"
                                >
                                    <MoreHorizontal class="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="w-40">
                                <DropdownMenuItem @click="editNote(note)">
                                    <Pencil class="size-4" />
                                    Edit note
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="togglePin(note)">
                                    <PinOff
                                        v-if="note.is_pinned"
                                        class="size-4"
                                    />
                                    <Pin v-else class="size-4" />
                                    {{ note.is_pinned ? 'Unpin' : 'Pin' }}
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    variant="destructive"
                                    @click="removeNote(note)"
                                >
                                    <Trash2 class="size-4" />
                                    Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <p
                    class="mt-3 line-clamp-[12] font-mono text-[12.5px] leading-relaxed whitespace-pre-line text-foreground/80"
                >
                    {{ note.body }}
                </p>

                <div
                    v-if="(note.tags ?? []).length"
                    class="mt-4 flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="tag in note.tags ?? []"
                        :key="tag"
                        class="rounded-md border border-current/10 bg-background/50 px-2 py-0.5 text-[10px] font-medium text-foreground/60"
                    >
                        #{{ tag }}
                    </span>
                </div>
            </article>
        </div>

        <EmptyState
            v-else
            :icon="NotebookPen"
            title="No notes yet"
            description="Capture a snippet, a decision or a thought worth keeping."
        >
            <Button @click="createNote">
                <Plus class="size-4" />
                New note
            </Button>
        </EmptyState>
    </div>

    <NoteDialog v-model:open="dialogOpen" :note="editing" />
</template>
