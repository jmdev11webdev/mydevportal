<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Check, ChevronDown, Plus, Search, X } from '@lucide/vue';
import TechStackIcon from '@/components/portal/TechStackIcon.vue';
import {
    getTechBadgeStyle,
    techStackCatalog,
    techStackGroups,
    type TechOption,
} from '@/lib/techStack';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        modelValue: string[];
        multiple?: boolean;
        placeholder?: string;
        disabled?: boolean;
        id?: string;
    }>(),
    {
        multiple: true,
        placeholder: 'Select technologies...',
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
    selectOption: [option: TechOption];
}>();

const rootRef = ref<HTMLElement | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);
const open = ref(false);
const query = ref('');
const activeGroup = ref<string>('All');

const selectedSet = computed(
    () => new Set(props.modelValue.map((item) => item.toLowerCase())),
);

const filteredGroups = computed(() => {
    const term = query.value.trim().toLowerCase();

    return techStackGroups
        .filter(
            (group) =>
                activeGroup.value === 'All' || activeGroup.value === group,
        )
        .map((group) => ({
            group,
            items: techStackCatalog.filter((option) => {
                if (option.group !== group) {
                    return false;
                }

                if (!term) {
                    return true;
                }

                return (
                    option.label.toLowerCase().includes(term) ||
                    option.aliases?.some((alias) =>
                        alias.toLowerCase().includes(term),
                    )
                );
            }),
        }))
        .filter((section) => section.items.length > 0);
});

const canAddCustom = computed(() => {
    const trimmed = query.value.trim();
    if (!trimmed) {
        return false;
    }

    const lower = trimmed.toLowerCase();
    const existsInCatalog = techStackCatalog.some(
        (opt) => opt.label.toLowerCase() === lower,
    );
    const existsSelected = selectedSet.value.has(lower);

    return !existsInCatalog && !existsSelected;
});

function toggleOpen(): void {
    if (props.disabled) {
        return;
    }

    open.value = !open.value;
    if (open.value) {
        requestAnimationFrame(() => searchInputRef.value?.focus());
    }
}

function close(): void {
    open.value = false;
}

function isSelected(label: string): boolean {
    return selectedSet.value.has(label.toLowerCase());
}

function toggleOption(option: TechOption): void {
    if (props.disabled) {
        return;
    }

    if (!props.multiple) {
        emit('update:modelValue', [option.label]);
        emit('selectOption', option);
        open.value = false;
        return;
    }

    if (isSelected(option.label)) {
        emit(
            'update:modelValue',
            props.modelValue.filter(
                (item) => item.toLowerCase() !== option.label.toLowerCase(),
            ),
        );
    } else {
        emit('update:modelValue', [...props.modelValue, option.label]);
        emit('selectOption', option);
    }
}

function addCustom(): void {
    const trimmed = query.value.trim();
    if (!trimmed || props.disabled) {
        return;
    }

    if (!props.multiple) {
        emit('update:modelValue', [trimmed]);
        query.value = '';
        open.value = false;
        return;
    }

    if (!isSelected(trimmed)) {
        emit('update:modelValue', [...props.modelValue, trimmed]);
    }
    query.value = '';
}

function removeItem(label: string): void {
    if (props.disabled) {
        return;
    }

    emit(
        'update:modelValue',
        props.modelValue.filter(
            (item) => item.toLowerCase() !== label.toLowerCase(),
        ),
    );
}

function handleOutsideClick(event: MouseEvent): void {
    if (!open.value || !rootRef.value) {
        return;
    }

    if (!rootRef.value.contains(event.target as Node)) {
        close();
    }
}

onMounted(() => {
    document.addEventListener('mousedown', handleOutsideClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleOutsideClick);
});
</script>

<template>
    <div ref="rootRef" class="relative w-full">
        <div
            :id="id"
            role="button"
            tabindex="0"
            :aria-expanded="open"
            :aria-disabled="disabled"
            :class="
                cn(
                    'flex min-h-9 w-full flex-wrap items-center justify-between gap-2 rounded-md border border-input bg-background px-2.5 py-1.5 text-sm shadow-xs transition-[color,box-shadow] outline-none dark:bg-input/30',
                    disabled
                        ? 'cursor-not-allowed opacity-50'
                        : 'cursor-pointer hover:bg-accent/40 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:hover:bg-input/50',
                )
            "
            @click="toggleOpen"
            @keydown.enter.prevent="toggleOpen"
            @keydown.space.prevent="toggleOpen"
            @keydown.esc="close"
        >
            <div class="flex flex-1 flex-wrap items-center gap-1.5">
                <span
                    v-if="modelValue.length === 0"
                    class="px-0.5 text-sm text-muted-foreground"
                >
                    {{ placeholder }}
                </span>

                <span
                    v-for="item in modelValue"
                    :key="item"
                    :style="getTechBadgeStyle(item)"
                    class="inline-flex items-center gap-1.5 rounded-md border px-2 py-0.5 text-xs font-medium text-foreground"
                >
                    <TechStackIcon :name="item" />
                    <span>{{ item }}</span>
                    <button
                        v-if="!disabled"
                        type="button"
                        class="rounded-xs text-muted-foreground transition-colors hover:text-foreground"
                        :aria-label="`Remove ${item}`"
                        @click.stop="removeItem(item)"
                    >
                        <X class="size-3" />
                    </button>
                </span>
            </div>

            <ChevronDown
                class="size-4 shrink-0 text-muted-foreground transition-transform duration-150"
                :class="open ? 'rotate-180' : ''"
            />
        </div>

        <div
            v-if="open"
            class="absolute top-full right-0 left-0 z-50 mt-1.5 overflow-hidden rounded-lg border border-border bg-popover text-popover-foreground shadow-lg ring-1 ring-black/5 dark:ring-white/10"
            @keydown.esc.stop="close"
        >
            <div class="border-b border-border p-2">
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        ref="searchInputRef"
                        v-model="query"
                        type="text"
                        placeholder="Search Devicon stack (e.g. Laravel, Vue, Docker)..."
                        class="h-8 w-full rounded-md border border-input bg-background pr-2.5 pl-8 text-xs text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none dark:bg-input/30"
                        @keydown.enter.prevent="
                            canAddCustom ? addCustom() : undefined
                        "
                    />
                </div>

                <div class="mt-2 flex flex-wrap gap-1">
                    <button
                        v-for="tab in ['All', ...techStackGroups]"
                        :key="tab"
                        type="button"
                        class="rounded-md px-2 py-0.5 text-[11px] font-medium transition-colors"
                        :class="
                            activeGroup === tab
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                        @click="activeGroup = tab"
                    >
                        {{ tab }}
                    </button>
                </div>
            </div>

            <div class="max-h-60 overflow-y-auto p-1.5">
                <button
                    v-if="canAddCustom"
                    type="button"
                    class="mb-1 flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-left text-xs font-medium text-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
                    @click="addCustom"
                >
                    <Plus class="size-3.5 text-muted-foreground" />
                    <span>Add "{{ query.trim() }}"</span>
                </button>

                <div
                    v-if="filteredGroups.length === 0 && !canAddCustom"
                    class="px-3 py-6 text-center text-xs text-muted-foreground"
                >
                    No matching stack icons found.
                </div>

                <div
                    v-for="section in filteredGroups"
                    :key="section.group"
                    class="mb-2 last:mb-0"
                >
                    <p
                        class="px-2 py-1 text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        {{ section.group }}
                    </p>
                    <div class="grid grid-cols-2 gap-1 sm:grid-cols-3">
                        <button
                            v-for="option in section.items"
                            :key="option.label"
                            type="button"
                            class="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 text-left text-xs transition-colors"
                            :class="
                                isSelected(option.label)
                                    ? 'bg-accent font-medium text-accent-foreground'
                                    : 'text-popover-foreground hover:bg-accent/60 hover:text-accent-foreground'
                            "
                            @click="toggleOption(option)"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <TechStackIcon :name="option.label" />
                                <span class="truncate">{{ option.label }}</span>
                            </span>
                            <Check
                                v-if="isSelected(option.label)"
                                class="size-3.5 shrink-0 text-primary"
                            />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
