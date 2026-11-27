<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ExternalLink,
    Layers,
    MoreHorizontal,
    Pencil,
    Pin,
    PinOff,
    Plus,
    Search,
    Trash2,
} from '@lucide/vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import StackDialog from '@/components/portal/StackDialog.vue';
import TechStackIcon from '@/components/portal/TechStackIcon.vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    stackCategories,
    stackCategoryClasses,
    stackCategoryLabel,
    stackStatuses,
    stackStatusClasses,
    stackStatusLabel,
} from '@/lib/colors';
import { getTechBadgeStyle } from '@/lib/techStack';
import type {
    StackCategory,
    StackItem,
    StackStatus,
    StackSummary,
} from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Stack', href: '/stack' }],
    },
});

const props = defineProps<{
    items: StackItem[];
    summary: StackSummary;
}>();

const dialogOpen = ref(false);
const editing = ref<StackItem | null>(null);
const categoryFilter = ref<'all' | StackCategory>('all');
const statusFilter = ref<'all' | StackStatus>('all');
const search = ref('');

const filteredItems = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.items.filter((item) => {
        if (
            categoryFilter.value !== 'all' &&
            item.category !== categoryFilter.value
        ) {
            return false;
        }

        if (
            statusFilter.value !== 'all' &&
            item.status !== statusFilter.value
        ) {
            return false;
        }

        if (!term) {
            return true;
        }

        return [
            item.name,
            item.version,
            item.notes,
            stackCategoryLabel[item.category],
            stackStatusLabel[item.status],
        ].some((field) => field?.toLowerCase().includes(term));
    });
});

function openCreate(): void {
    editing.value = null;
    dialogOpen.value = true;
}

function openEdit(item: StackItem): void {
    editing.value = item;
    dialogOpen.value = true;
}

function togglePin(item: StackItem): void {
    router.patch(
        `/stack/${item.id}/pin`,
        {},
        {
            preserveScroll: true,
        },
    );
}

function destroyItem(item: StackItem): void {
    router.delete(`/stack/${item.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Stack" />

    <div class="flex flex-1 flex-col gap-6 p-4 pb-24 md:p-6 md:pb-6">
        <PageHeader
            eyebrow="Tech Radar"
            title="Stack"
            description="Your personal inventory of languages, frameworks, databases, and developer tools."
            :icon="Layers"
        >
            <template #actions>
                <Button
                    size="sm"
                    class="hidden sm:inline-flex"
                    @click="openCreate"
                >
                    <Plus class="size-4" />
                    Add tool
                </Button>
            </template>
        </PageHeader>

        <!-- Summary Stats -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Total tools" :value="summary.total" />
            <StatCard
                label="Core stack"
                :value="summary.core"
                accent="emerald"
            />
            <StatCard
                label="Learning"
                :value="summary.learning"
                accent="amber"
            />
            <StatCard
                label="Evaluating"
                :value="summary.evaluating"
                accent="sky"
            />
        </div>

        <!-- Filters -->
        <div class="flex flex-col gap-3 rounded-xl border bg-card p-3">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="hidden flex-wrap items-center gap-1.5 sm:flex">
                    <button
                        type="button"
                        class="rounded-lg border px-2.5 py-1 text-xs font-medium transition-colors"
                        :class="
                            categoryFilter === 'all'
                                ? 'border-foreground/20 bg-foreground text-background'
                                : 'border-transparent bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                        @click="categoryFilter = 'all'"
                    >
                        All categories
                    </button>
                    <button
                        v-for="cat in stackCategories"
                        :key="cat"
                        type="button"
                        class="rounded-lg border px-2.5 py-1 text-xs font-medium transition-colors"
                        :class="
                            categoryFilter === cat
                                ? stackCategoryClasses[cat]
                                : 'border-transparent bg-muted/50 text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                        @click="categoryFilter = cat"
                    >
                        {{ stackCategoryLabel[cat] }}
                    </button>
                </div>

                <div class="flex items-center gap-2 sm:w-56">
                    <div class="relative min-w-0 flex-1">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="search"
                            type="search"
                            placeholder="Search stack..."
                            class="h-8 pl-8 text-xs"
                        />
                    </div>
                    <Select v-model="categoryFilter">
                        <SelectTrigger
                            class="h-8 w-36 shrink-0 text-xs sm:hidden"
                            aria-label="Filter stack by category"
                        >
                            <SelectValue placeholder="All categories" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All categories</SelectItem>
                            <SelectItem
                                v-for="cat in stackCategories"
                                :key="cat"
                                :value="cat"
                            >
                                {{ stackCategoryLabel[cat] }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div class="flex items-center justify-between gap-2 border-t pt-2.5 sm:hidden">
                <span class="text-[11px] font-medium text-muted-foreground">
                    Status
                </span>
                <Select v-model="statusFilter">
                    <SelectTrigger
                        class="h-8 w-40 text-xs"
                        aria-label="Filter stack by status"
                    >
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        <SelectItem
                            v-for="status in stackStatuses"
                            :key="status"
                            :value="status"
                        >
                            {{ stackStatusLabel[status] }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="hidden flex-wrap items-center gap-1.5 border-t pt-2.5 sm:flex">
                <span
                    class="mr-1 text-[11px] font-medium text-muted-foreground"
                >
                    Status:
                </span>
                <button
                    type="button"
                    class="rounded-md px-2 py-0.5 text-xs font-medium transition-colors"
                    :class="
                        statusFilter === 'all'
                            ? 'bg-secondary text-secondary-foreground'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    @click="statusFilter = 'all'"
                >
                    All
                </button>
                <button
                    v-for="status in stackStatuses"
                    :key="status"
                    type="button"
                    class="rounded-md border px-2 py-0.5 text-xs font-medium transition-colors"
                    :class="
                        statusFilter === status
                            ? stackStatusClasses[status]
                            : 'border-transparent text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    @click="statusFilter = status"
                >
                    {{ stackStatusLabel[status] }}
                </button>
            </div>
        </div>

        <!-- Grid -->
        <EmptyState
            v-if="filteredItems.length === 0"
            :icon="Layers"
            :title="
                items.length === 0
                    ? 'No stack items yet'
                    : 'No matching stack items'
            "
            :description="
                items.length === 0
                    ? 'Add your daily-driver frameworks, databases, and tools to keep your tech radar organized.'
                    : 'Try clearing your search or switching category and status filters.'
            "
        >
            <template v-if="items.length === 0" #action>
                <Button size="sm" @click="openCreate">
                    <Plus class="size-4" />
                    Add first tool
                </Button>
            </template>
        </EmptyState>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article
                v-for="item in filteredItems"
                :key="item.id"
                class="group flex flex-col justify-between gap-3 rounded-xl border bg-card p-4 shadow-xs transition-colors hover:border-foreground/20"
            >
                <div class="flex flex-col gap-2.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                            <span
                                :style="getTechBadgeStyle(item.name)"
                                class="inline-flex size-7 shrink-0 items-center justify-center rounded-lg border"
                            >
                                <TechStackIcon
                                    :name="item.name"
                                    class="text-base"
                                />
                            </span>
                            <h2
                                class="truncate text-base font-semibold tracking-tight"
                            >
                                {{ item.name }}
                            </h2>
                            <span
                                v-if="item.version"
                                class="rounded-md bg-muted px-1.5 py-0.5 font-mono text-[11px] text-muted-foreground"
                            >
                                {{ item.version }}
                            </span>
                            <Pin
                                v-if="item.is_pinned"
                                class="size-3.5 shrink-0 text-amber-500"
                            />
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7 shrink-0 text-muted-foreground"
                                    aria-label="Stack item actions"
                                >
                                    <MoreHorizontal class="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem @click="openEdit(item)">
                                    <Pencil class="size-4" />
                                    Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem @click="togglePin(item)">
                                    <PinOff
                                        v-if="item.is_pinned"
                                        class="size-4"
                                    />
                                    <Pin v-else class="size-4" />
                                    {{
                                        item.is_pinned ? 'Unpin' : 'Pin to top'
                                    }}
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    class="text-destructive focus:text-destructive"
                                    @click="destroyItem(item)"
                                >
                                    <Trash2 class="size-4" />
                                    Remove
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        <Badge
                            variant="outline"
                            :class="stackCategoryClasses[item.category]"
                        >
                            {{ stackCategoryLabel[item.category] }}
                        </Badge>
                        <Badge
                            variant="outline"
                            :class="stackStatusClasses[item.status]"
                        >
                            {{ stackStatusLabel[item.status] }}
                        </Badge>
                    </div>

                    <p
                        v-if="item.notes"
                        class="line-clamp-3 text-sm text-muted-foreground"
                    >
                        {{ item.notes }}
                    </p>
                </div>

                <div v-if="item.url" class="border-t pt-2.5">
                    <a
                        :href="item.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ExternalLink class="size-3.5" />
                        Documentation
                    </a>
                </div>
            </article>
        </div>

        <StackDialog
            v-model:open="dialogOpen"
            :item="editing"
            :default-category="
                categoryFilter === 'all' ? 'backend' : categoryFilter
            "
        />

        <div class="fixed right-5 bottom-5 z-40 sm:hidden">
            <Button
                size="icon"
                class="size-14 rounded-full shadow-lg"
                aria-label="Add tool"
                @click="openCreate"
            >
                <Plus class="size-6" />
            </Button>
        </div>
    </div>
</template>
