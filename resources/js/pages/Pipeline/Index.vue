<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Plus, Search, Trash2, TrendingUp } from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    pipelineStageClasses,
    pipelineStageLabel,
    pipelineStages,
} from '@/lib/colors';
import type {
    PipelineLead,
    PipelineStage,
    PipelineSummary,
} from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pipeline', href: '/pipeline' }],
    },
});

const props = defineProps<{
    leads: PipelineLead[];
    summary: PipelineSummary;
}>();

type EditableField =
    | 'name'
    | 'company'
    | 'contact'
    | 'stage'
    | 'source'
    | 'value'
    | 'follow_up_date'
    | 'notes';

type DraftRow = {
    name: string;
    company: string;
    contact: string;
    stage: PipelineStage;
    source: string;
    value: string;
    follow_up_date: string;
    notes: string;
};

const activeStage = ref<'all' | PipelineStage>('all');
const searchQuery = ref('');
const quickAddInput = ref<HTMLInputElement | null>(null);
const savingCell = ref<string | null>(null);

const drafts = reactive<Record<number, DraftRow>>({});

const syncDrafts = (leads: PipelineLead[]) => {
    const validIds = new Set(leads.map((lead) => lead.id));
    for (const id of Object.keys(drafts)) {
        if (!validIds.has(Number(id))) {
            delete drafts[Number(id)];
        }
    }

    for (const lead of leads) {
        drafts[lead.id] = {
            name: lead.name ?? '',
            company: lead.company ?? '',
            contact: lead.contact ?? '',
            stage: lead.stage,
            source: lead.source ?? '',
            value: lead.value !== null ? String(lead.value) : '',
            follow_up_date: lead.follow_up_date
                ? lead.follow_up_date.slice(0, 10)
                : '',
            notes: lead.notes ?? '',
        };
    }
};

watch(() => props.leads, syncDrafts, { immediate: true });

const newRow = reactive<DraftRow>({
    name: '',
    company: '',
    contact: '',
    stage: 'new',
    source: '',
    value: '',
    follow_up_date: '',
    notes: '',
});

const filteredLeads = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return props.leads.filter((lead) => {
        if (activeStage.value !== 'all' && lead.stage !== activeStage.value) {
            return false;
        }

        if (!query) {
            return true;
        }

        return [
            lead.name,
            lead.company,
            lead.contact,
            lead.source,
            lead.notes,
            pipelineStageLabel[lead.stage],
        ].some((field) => field?.toLowerCase().includes(query));
    });
});

const formatCurrency = (amount: number): string =>
    new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(amount);

const normalizeValue = (
    field: EditableField,
    raw: string,
): string | number | null => {
    const trimmed = raw.trim();
    if (field === 'value') {
        if (trimmed === '') return null;
        const parsed = Number.parseInt(trimmed, 10);
        return Number.isNaN(parsed) || parsed < 0 ? null : parsed;
    }
    if (field === 'name' || field === 'stage') {
        return trimmed;
    }
    return trimmed === '' ? null : trimmed;
};

const originalFieldValue = (
    lead: PipelineLead,
    field: EditableField,
): string | number | null => {
    if (field === 'follow_up_date') {
        return lead.follow_up_date ? lead.follow_up_date.slice(0, 10) : null;
    }
    return lead[field];
};

const commitCell = (lead: PipelineLead, field: EditableField) => {
    const draft = drafts[lead.id];
    if (!draft) return;

    const nextValue = normalizeValue(field, draft[field]);
    if (field === 'name' && !nextValue) {
        draft.name = lead.name;
        return;
    }

    const prevValue = originalFieldValue(lead, field);
    if (nextValue === prevValue) {
        return;
    }

    const cellKey = `${lead.id}:${field}`;
    savingCell.value = cellKey;

    router.patch(
        `/pipeline/${lead.id}`,
        { [field]: nextValue },
        {
            preserveScroll: true,
            onFinish: () => {
                if (savingCell.value === cellKey) {
                    savingCell.value = null;
                }
            },
            onError: () => {
                syncDrafts(props.leads);
            },
        },
    );
};

const revertCell = (lead: PipelineLead, field: EditableField, e: Event) => {
    const draft = drafts[lead.id];
    if (!draft) return;

    if (field === 'stage') {
        draft.stage = lead.stage;
    } else {
        const prev = originalFieldValue(lead, field);
        draft[field] = prev !== null ? String(prev) : '';
    }
    (e.target as HTMLElement | null)?.blur();
};

const blurOnEnter = (e: KeyboardEvent) => {
    (e.target as HTMLElement | null)?.blur();
};

const resetNewRow = () => {
    newRow.name = '';
    newRow.company = '';
    newRow.contact = '';
    newRow.stage = activeStage.value === 'all' ? 'new' : activeStage.value;
    newRow.source = '';
    newRow.value = '';
    newRow.follow_up_date = '';
    newRow.notes = '';
};

const addLead = () => {
    const name = newRow.name.trim();
    if (!name) {
        quickAddInput.value?.focus();
        return;
    }

    router.post(
        '/pipeline',
        {
            name,
            company: normalizeValue('company', newRow.company),
            contact: normalizeValue('contact', newRow.contact),
            stage: newRow.stage,
            source: normalizeValue('source', newRow.source),
            value: normalizeValue('value', newRow.value),
            follow_up_date: normalizeValue(
                'follow_up_date',
                newRow.follow_up_date,
            ),
            notes: normalizeValue('notes', newRow.notes),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                resetNewRow();
                quickAddInput.value?.focus();
            },
        },
    );
};

const removeLead = (lead: PipelineLead) => {
    router.delete(`/pipeline/${lead.id}`, {
        preserveScroll: true,
    });
};

const focusQuickAdd = () => {
    if (activeStage.value !== 'all') {
        newRow.stage = activeStage.value;
    }
    quickAddInput.value?.focus();
};
</script>

<template>
    <Head title="Pipeline" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Outreach & CRM"
            title="Pipeline"
            description="Spreadsheet-style lead tracker with pipeline stages. Edit any cell inline — changes save automatically."
            :icon="TrendingUp"
        >
            <template #actions>
                <Button size="sm" @click="focusQuickAdd">
                    <Plus class="size-4" />
                    New lead
                </Button>
            </template>
        </PageHeader>

        <!-- Pipeline Stage Summary & Filter Bar -->
        <div
            class="flex flex-col gap-3 rounded-xl border bg-card p-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors"
                    :class="
                        activeStage === 'all'
                            ? 'border-foreground/20 bg-foreground text-background'
                            : 'border-transparent bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    @click="activeStage = 'all'"
                >
                    <span>All</span>
                    <span class="tabular-nums opacity-75">
                        {{ summary.total_count }}
                    </span>
                </button>

                <button
                    v-for="stage in pipelineStages"
                    :key="stage"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors"
                    :class="
                        activeStage === stage
                            ? pipelineStageClasses[stage]
                            : 'border-transparent bg-muted/50 text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    @click="activeStage = stage"
                >
                    <span>{{ pipelineStageLabel[stage] }}</span>
                    <span class="tabular-nums opacity-75">
                        {{ summary.stages[stage]?.count ?? 0 }}
                    </span>
                    <span
                        v-if="(summary.stages[stage]?.value ?? 0) > 0"
                        class="hidden tabular-nums opacity-70 md:inline"
                    >
                        · {{ formatCurrency(summary.stages[stage].value) }}
                    </span>
                </button>
            </div>

            <div class="flex items-center gap-3">
                <div
                    class="hidden items-center gap-3 text-xs text-muted-foreground xl:flex"
                >
                    <span>
                        Active:
                        <strong
                            class="font-semibold text-foreground tabular-nums"
                        >
                            {{ formatCurrency(summary.active_value) }}
                        </strong>
                    </span>
                    <span>
                        Won:
                        <strong
                            class="font-semibold text-emerald-600 tabular-nums dark:text-emerald-400"
                        >
                            {{ formatCurrency(summary.won_value) }}
                        </strong>
                    </span>
                </div>

                <div class="relative w-full sm:w-56">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="searchQuery"
                        type="search"
                        placeholder="Filter leads..."
                        class="h-8 pl-8 text-xs"
                    />
                </div>
            </div>
        </div>

        <!-- Spreadsheet Grid -->
        <div class="overflow-hidden rounded-xl border bg-card shadow-xs">
            <div
                tabindex="0"
                role="region"
                aria-label="Pipeline leads spreadsheet"
                class="pipeline-sheet-wrapper"
            >
                <table class="pipeline-sheet w-full text-left text-xs">
                    <thead>
                        <tr
                            class="border-b bg-muted/80 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase backdrop-blur-xs"
                        >
                            <th
                                class="sticky-col min-w-44 border-r bg-muted/95 px-3 py-2.5"
                            >
                                Lead
                            </th>
                            <th class="min-w-36 border-r px-3 py-2.5">
                                Company
                            </th>
                            <th class="min-w-44 border-r px-3 py-2.5">
                                Contact
                            </th>
                            <th class="min-w-32 border-r px-3 py-2.5">Stage</th>
                            <th class="min-w-28 border-r px-3 py-2.5">
                                Source
                            </th>
                            <th
                                class="min-w-24 border-r px-3 py-2.5 text-right"
                            >
                                Value ($)
                            </th>
                            <th class="min-w-32 border-r px-3 py-2.5">
                                Follow-up
                            </th>
                            <th class="min-w-56 border-r px-3 py-2.5">Notes</th>
                            <th class="w-10 px-2 py-2.5 text-center">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y">
                        <tr
                            v-for="lead in filteredLeads"
                            :key="lead.id"
                            class="group transition-colors hover:bg-muted/30"
                        >
                            <th
                                scope="row"
                                class="sticky-col border-r bg-card p-0 font-medium group-hover:bg-muted/30"
                            >
                                <input
                                    v-model="drafts[lead.id].name"
                                    type="text"
                                    aria-label="Lead name"
                                    class="sheet-input font-medium text-foreground"
                                    @blur="commitCell(lead, 'name')"
                                    @keydown.enter="blurOnEnter"
                                    @keydown.esc="
                                        revertCell(lead, 'name', $event)
                                    "
                                />
                            </th>

                            <td data-label="Company" class="border-r p-0">
                                <input
                                    v-model="drafts[lead.id].company"
                                    type="text"
                                    placeholder="—"
                                    aria-label="Company"
                                    class="sheet-input"
                                    @blur="commitCell(lead, 'company')"
                                    @keydown.enter="blurOnEnter"
                                    @keydown.esc="
                                        revertCell(lead, 'company', $event)
                                    "
                                />
                            </td>

                            <td data-label="Contact" class="border-r p-0">
                                <input
                                    v-model="drafts[lead.id].contact"
                                    type="text"
                                    placeholder="Email or link"
                                    aria-label="Contact"
                                    class="sheet-input"
                                    @blur="commitCell(lead, 'contact')"
                                    @keydown.enter="blurOnEnter"
                                    @keydown.esc="
                                        revertCell(lead, 'contact', $event)
                                    "
                                />
                            </td>

                            <td data-label="Stage" class="border-r px-2 py-1">
                                <Select
                                    v-model="drafts[lead.id].stage"
                                    @update:model-value="
                                        commitCell(lead, 'stage')
                                    "
                                >
                                    <SelectTrigger
                                        aria-label="Stage"
                                        size="sm"
                                        class="h-7! w-full text-xs font-medium"
                                        :class="
                                            pipelineStageClasses[
                                                drafts[lead.id].stage
                                            ]
                                        "
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="stage in pipelineStages"
                                            :key="stage"
                                            :value="stage"
                                        >
                                            {{ pipelineStageLabel[stage] }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </td>

                            <td data-label="Source" class="border-r p-0">
                                <input
                                    v-model="drafts[lead.id].source"
                                    type="text"
                                    placeholder="—"
                                    aria-label="Source"
                                    class="sheet-input"
                                    @blur="commitCell(lead, 'source')"
                                    @keydown.enter="blurOnEnter"
                                    @keydown.esc="
                                        revertCell(lead, 'source', $event)
                                    "
                                />
                            </td>

                            <td data-label="Value" class="border-r p-0">
                                <input
                                    v-model="drafts[lead.id].value"
                                    type="number"
                                    min="0"
                                    step="1"
                                    placeholder="0"
                                    aria-label="Estimated value"
                                    class="sheet-input text-right tabular-nums"
                                    @blur="commitCell(lead, 'value')"
                                    @keydown.enter="blurOnEnter"
                                    @keydown.esc="
                                        revertCell(lead, 'value', $event)
                                    "
                                />
                            </td>

                            <td data-label="Follow-up" class="border-r p-0">
                                <input
                                    v-model="drafts[lead.id].follow_up_date"
                                    type="date"
                                    aria-label="Follow-up date"
                                    class="sheet-input tabular-nums"
                                    @change="commitCell(lead, 'follow_up_date')"
                                />
                            </td>

                            <td data-label="Notes" class="border-r p-0">
                                <input
                                    v-model="drafts[lead.id].notes"
                                    type="text"
                                    placeholder="Add a note..."
                                    aria-label="Notes"
                                    class="sheet-input"
                                    @blur="commitCell(lead, 'notes')"
                                    @keydown.enter="blurOnEnter"
                                    @keydown.esc="
                                        revertCell(lead, 'notes', $event)
                                    "
                                />
                            </td>

                            <td
                                data-label="Actions"
                                class="px-1.5 py-1 text-center"
                            >
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7 text-muted-foreground opacity-60 group-hover:opacity-100 hover:text-destructive"
                                    aria-label="Delete lead"
                                    @click="removeLead(lead)"
                                >
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </td>
                        </tr>

                        <tr v-if="filteredLeads.length === 0">
                            <td
                                colspan="9"
                                class="px-4 py-8 text-center text-xs text-muted-foreground"
                            >
                                {{
                                    leads.length === 0
                                        ? 'No leads yet — add your first lead in the row below.'
                                        : 'No leads match the current filter.'
                                }}
                            </td>
                        </tr>

                        <!-- Quick-Add Bottom Row -->
                        <tr class="bg-muted/25">
                            <th
                                scope="row"
                                class="sticky-col border-t border-r bg-muted/40 p-0 font-normal"
                            >
                                <input
                                    ref="quickAddInput"
                                    v-model="newRow.name"
                                    type="text"
                                    placeholder="+ Add lead name..."
                                    aria-label="New lead name"
                                    class="sheet-input font-medium placeholder:text-muted-foreground/70"
                                    @keydown.enter.prevent="addLead"
                                />
                            </th>
                            <td
                                data-label="Company"
                                class="border-t border-r p-0"
                            >
                                <input
                                    v-model="newRow.company"
                                    type="text"
                                    placeholder="Company"
                                    aria-label="New lead company"
                                    class="sheet-input"
                                    @keydown.enter.prevent="addLead"
                                />
                            </td>
                            <td
                                data-label="Contact"
                                class="border-t border-r p-0"
                            >
                                <input
                                    v-model="newRow.contact"
                                    type="text"
                                    placeholder="Email or link"
                                    aria-label="New lead contact"
                                    class="sheet-input"
                                    @keydown.enter.prevent="addLead"
                                />
                            </td>
                            <td
                                data-label="Stage"
                                class="border-t border-r px-2 py-1"
                            >
                                <Select v-model="newRow.stage">
                                    <SelectTrigger
                                        aria-label="New lead stage"
                                        size="sm"
                                        class="h-7! w-full text-xs font-medium"
                                        :class="
                                            pipelineStageClasses[newRow.stage]
                                        "
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="stage in pipelineStages"
                                            :key="stage"
                                            :value="stage"
                                        >
                                            {{ pipelineStageLabel[stage] }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </td>
                            <td
                                data-label="Source"
                                class="border-t border-r p-0"
                            >
                                <input
                                    v-model="newRow.source"
                                    type="text"
                                    placeholder="Source"
                                    aria-label="New lead source"
                                    class="sheet-input"
                                    @keydown.enter.prevent="addLead"
                                />
                            </td>
                            <td
                                data-label="Value"
                                class="border-t border-r p-0"
                            >
                                <input
                                    v-model="newRow.value"
                                    type="number"
                                    min="0"
                                    step="1"
                                    placeholder="0"
                                    aria-label="New lead value"
                                    class="sheet-input text-right tabular-nums"
                                    @keydown.enter.prevent="addLead"
                                />
                            </td>
                            <td
                                data-label="Follow-up"
                                class="border-t border-r p-0"
                            >
                                <input
                                    v-model="newRow.follow_up_date"
                                    type="date"
                                    aria-label="New lead follow-up date"
                                    class="sheet-input tabular-nums"
                                    @keydown.enter.prevent="addLead"
                                />
                            </td>
                            <td
                                data-label="Notes"
                                class="border-t border-r p-0"
                            >
                                <input
                                    v-model="newRow.notes"
                                    type="text"
                                    placeholder="Notes..."
                                    aria-label="New lead notes"
                                    class="sheet-input"
                                    @keydown.enter.prevent="addLead"
                                />
                            </td>
                            <td
                                data-label="Add"
                                class="border-t px-1.5 py-1 text-center"
                            >
                                <Button
                                    size="icon"
                                    variant="secondary"
                                    class="size-7"
                                    aria-label="Add lead row"
                                    @click="addLead"
                                >
                                    <Plus class="size-3.5" />
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pipeline-sheet-wrapper {
    overflow: auto;
    max-inline-size: 100%;
    max-block-size: min(640px, 72vh);
    container-type: inline-size;
}

.pipeline-sheet {
    border-collapse: separate;
    border-spacing: 0;
}

.pipeline-sheet thead {
    position: sticky;
    inset-block-start: 0;
    z-index: 20;
}

.pipeline-sheet .sticky-col {
    position: sticky;
    inset-inline-start: 0;
    z-index: 10;
}

.pipeline-sheet thead .sticky-col {
    z-index: 30;
}

.sheet-input {
    display: block;
    width: 100%;
    height: 2.25rem;
    padding: 0.375rem 0.75rem;
    background: transparent;
    font-size: 0.75rem;
    line-height: 1.25;
    outline: none;
    transition:
        background-color 120ms ease,
        box-shadow 120ms ease;
}

.sheet-input:focus {
    background-color: color-mix(
        in oklab,
        var(--color-background) 85%,
        var(--color-primary) 15%
    );
    box-shadow: inset 0 0 0 1.5px var(--color-ring);
}

@container (width < 640px) {
    .pipeline-sheet,
    .pipeline-sheet thead,
    .pipeline-sheet tbody,
    .pipeline-sheet tr,
    .pipeline-sheet th,
    .pipeline-sheet td {
        display: block;
    }

    .pipeline-sheet thead {
        display: none;
    }

    .pipeline-sheet tbody tr {
        margin-block-end: 0.75rem;
        border: 1px solid var(--color-border);
        border-radius: 0.5rem;
        overflow: hidden;
    }

    .pipeline-sheet tbody th.sticky-col {
        position: sticky;
        inset-block-start: 0;
        inset-inline-start: auto;
        z-index: 15;
        border-right: 0;
        border-bottom: 1px solid var(--color-border);
    }

    .pipeline-sheet tbody td {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        border-right: 0;
        padding-inline-start: 0.75rem;
    }

    .pipeline-sheet tbody td::before {
        content: attr(data-label) ': ';
        flex: 0 0 5.5rem;
        font-size: 0.6875rem;
        font-weight: 600;
        color: var(--color-muted-foreground);
    }
}
</style>
