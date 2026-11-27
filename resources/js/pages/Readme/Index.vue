<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    Download,
    FileCode,
    Plus,
    RotateCcw,
    Sparkles,
    Trash2,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import PageHeader from '@/components/PageHeader.vue';
import TechStackSelector from '@/components/portal/TechStackSelector.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { renderMarkdown } from '@/lib/markdown';
import { getTechMarkdownBadge } from '@/lib/techStack';
import type { Project } from '@/types/portal';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'README generator', href: '/readme' }],
    },
});

const props = defineProps<{
    projects: Project[];
}>();

type License = 'MIT' | 'Apache-2.0' | 'GPL-3.0' | 'BSD-3-Clause' | 'None';

type LinkRow = {
    repositoryUrl: string;
    liveUrl: string;
    portfolioUrl: string;
};

type EnabledFields = {
    tagline: boolean;
    description: boolean;
    features: boolean;
    techStack: boolean;
    installation: boolean;
    usage: boolean;
    links: boolean;
    license: boolean;
};

type ReadmeForm = {
    name: string;
    tagline: string;
    description: string;
    features: string;
    techStack: string;
    installation: string;
    usage: string;
    linkRows: LinkRow[];
    license: License;
    includeBadges: boolean;
    enabled: EnabledFields;
};

const storageKey = 'mydevportal.readme-generator.v3';
const scratchOption = 'scratch';
const repositoryPlaceholder = 'https://github.com/you/project';
const liveUrlPlaceholder = 'https://project.example.com';
const portfolioPlaceholder = 'https://portfolio.example.com';
const defaultPortfolioUrl = 'https://alexrivera.dev';

const defaultInstallation = [
    'git clone https://github.com/you/project.git',
    'cd project',
    'composer install',
    'npm install',
    'cp .env.example .env',
    'php artisan key:generate',
    'php artisan migrate',
    'npm run dev',
].join('\n');

const defaultFeatures = [
    'Clean developer workflow with zero tracking or clutter',
    'Consistent configuration and environment setup',
    'Built-in integration hooks for Orbit, Pulse API, Devfolio, and CLI Toolkit',
].join('\n');

const defaultUsage = ['# Verify local configuration', 'php artisan about'].join(
    '\n',
);

const scratchEnabledDefaults: EnabledFields = {
    tagline: true,
    description: true,
    features: false,
    techStack: true,
    installation: true,
    usage: false,
    links: false,
    license: true,
};

const allEnabledFields: EnabledFields = {
    tagline: true,
    description: true,
    features: true,
    techStack: true,
    installation: true,
    usage: true,
    links: true,
    license: true,
};

const makeDefaultLinkRows = (): LinkRow[] => [
    {
        repositoryUrl: repositoryPlaceholder,
        liveUrl: liveUrlPlaceholder,
        portfolioUrl: portfolioPlaceholder,
    },
];

const defaults: ReadmeForm = {
    name: '',
    tagline: 'A short, honest description of what this project does.',
    description:
        'A focused developer tool designed to stay fast, predictable, and easy to self-host.',
    features: defaultFeatures,
    techStack: 'Laravel, Vue 3, TypeScript, SQLite',
    installation: defaultInstallation,
    usage: defaultUsage,
    linkRows: makeDefaultLinkRows(),
    license: 'MIT',
    includeBadges: true,
    enabled: { ...scratchEnabledDefaults },
};

type SuitePreset = Omit<ReadmeForm, 'enabled'>;

const suitePresets: Record<string, SuitePreset> = {
    orbit: {
        name: 'Orbit',
        tagline:
            'Privacy-first product analytics dashboard in the Alex Rivera developer suite.',
        description:
            'Orbit visualizes cookie-free aggregate product metrics streamed from Pulse API, publishes live project stats to Devfolio, and pairs with CLI Toolkit for local diagnostics.',
        features: [
            'Cookie-free, aggregate-only event analytics',
            'Ingests real-time telemetry streams from Pulse API',
            'Exposes privacy-safe summary widgets for Devfolio',
            'Supports local health checks via CLI Toolkit',
        ].join('\n'),
        techStack: 'Vue 3, TypeScript, Laravel, SQLite',
        installation: [
            'git clone https://github.com/alexrivera/orbit.git',
            'cd orbit',
            'composer install && npm install',
            'cp .env.example .env && php artisan key:generate',
            'php artisan migrate && npm run dev',
        ].join('\n'),
        usage: [
            '# Point Orbit to your Pulse API ingestion endpoint',
            'PULSE_API_URL=https://pulse.alexrivera.dev',
            'php artisan orbit:sync',
        ].join('\n'),
        linkRows: [
            {
                repositoryUrl: 'https://github.com/alexrivera/orbit',
                liveUrl: 'https://orbit.alexrivera.dev',
                portfolioUrl: defaultPortfolioUrl,
            },
        ],
        license: 'MIT',
        includeBadges: true,
    },
    'pulse api': {
        name: 'Pulse API',
        tagline:
            'Core REST and webhook ingestion service powering Orbit, Devfolio, and CLI Toolkit.',
        description:
            'Pulse API handles event ingestion, signed webhook deliveries, notifications, and background jobs across the entire suite—feeding Orbit dashboards, Devfolio changelogs, and CLI Toolkit logs.',
        features: [
            'High-throughput REST endpoints for Orbit analytics ingestion',
            'Signed webhook dispatchers feeding Devfolio release feeds',
            'Queue-backed retries, rate limiting, and Redis caching',
            'Streamable webhook logs inspected via CLI Toolkit',
        ].join('\n'),
        techStack: 'Laravel, PostgreSQL, Redis, Docker',
        installation: [
            'git clone https://github.com/alexrivera/pulse-api.git',
            'cd pulse-api',
            'composer install',
            'cp .env.example .env && php artisan key:generate',
            'php artisan migrate && php artisan queue:work',
        ].join('\n'),
        usage: [
            '# Dispatch a release event consumed by Orbit & Devfolio',
            'curl -X POST https://pulse.alexrivera.dev/api/events \\',
            '  -H "Authorization: Bearer $PULSE_TOKEN" \\',
            '  -d \'{"source":"devfolio","event":"release.published"}\'',
        ].join('\n'),
        linkRows: [
            {
                repositoryUrl: 'https://github.com/alexrivera/pulse-api',
                liveUrl: 'https://pulse.alexrivera.dev',
                portfolioUrl: defaultPortfolioUrl,
            },
        ],
        license: 'MIT',
        includeBadges: true,
    },
    devfolio: {
        name: 'Devfolio',
        tagline:
            'Minimal developer portfolio and living changelog synced with Pulse API and Orbit.',
        description:
            'Devfolio turns your builds and shipped milestones into a fast static site, pulling release entries from Pulse API webhooks, live metrics from Orbit, and markdown scaffolds from CLI Toolkit.',
        features: [
            'Static-first portfolio and automated release changelog',
            'Receives release webhook updates from Pulse API',
            'Displays privacy-first project pulse cards from Orbit',
            'Scaffolds new changelog entries from CLI Toolkit',
        ].join('\n'),
        techStack: 'Astro, Tailwind CSS, TypeScript',
        installation: [
            'git clone https://github.com/alexrivera/devfolio.git',
            'cd devfolio',
            'npm install',
            'cp .env.example .env',
            'npm run dev',
        ].join('\n'),
        usage: [
            '# Build static site with Orbit & Pulse API feeds',
            'ORBIT_ENDPOINT=https://orbit.alexrivera.dev/api/summary npm run build',
        ].join('\n'),
        linkRows: [
            {
                repositoryUrl: 'https://github.com/alexrivera/devfolio',
                liveUrl: 'https://alexrivera.dev',
                portfolioUrl: defaultPortfolioUrl,
            },
        ],
        license: 'MIT',
        includeBadges: true,
    },
    'cli toolkit': {
        name: 'CLI Toolkit',
        tagline: 'Fast terminal companion for Devfolio, Pulse API, and Orbit.',
        description:
            'CLI Toolkit provides a unified command-line workflow across the suite—scaffolding Devfolio changelog entries, tailing Pulse API webhook deliveries, and querying Orbit summaries locally.',
        features: [
            'Scaffolds new Devfolio posts and project changelogs',
            'Tails live Pulse API webhook deliveries and retries',
            'Queries Orbit daily metrics right from the terminal',
            'Caches local workspace state in embedded SQLite',
        ].join('\n'),
        techStack: 'Rust, Clap, SQLite',
        installation: [
            'git clone https://github.com/alexrivera/cli-toolkit.git',
            'cd cli-toolkit',
            'cargo build --release',
            'cargo install --path .',
        ].join('\n'),
        usage: [
            '# Scaffold a Devfolio changelog and ping Pulse API',
            'devkit post new "Orbit v1.2"',
            'devkit pulse tail --project orbit',
            'devkit orbit stats --today',
        ].join('\n'),
        linkRows: [
            {
                repositoryUrl: 'https://github.com/alexrivera/cli-toolkit',
                liveUrl: 'https://cli.alexrivera.dev',
                portfolioUrl: defaultPortfolioUrl,
            },
        ],
        license: 'MIT',
        includeBadges: true,
    },
};

const draft = reactive<ReadmeForm>({
    ...defaults,
    linkRows: makeDefaultLinkRows(),
    enabled: { ...scratchEnabledDefaults },
});
const selectedProject = ref<string>(scratchOption);
const copied = ref(false);
const copiedSource = ref(false);

const licenseOptions: License[] = [
    'MIT',
    'Apache-2.0',
    'GPL-3.0',
    'BSD-3-Clause',
    'None',
];

const selectedTechStack = computed<string[]>({
    get: () =>
        draft.techStack
            .split(',')
            .map((technology) => technology.trim())
            .filter((technology) => technology.length > 0),
    set: (items) => {
        draft.techStack = items.join(', ');
    },
});

const features = computed(() =>
    draft.features
        .split('\n')
        .map((feature) => feature.trim())
        .filter((feature) => feature.length > 0),
);

const githubRepository = computed(() => {
    if (!draft.enabled.links) {
        return null;
    }

    for (const row of draft.linkRows) {
        const match = row.repositoryUrl
            .trim()
            .match(/github\.com\/([^/]+)\/([^/?#]+)/i);

        if (match) {
            return `${match[1]}/${match[2].replace(/\.git$/, '')}`;
        }
    }

    return null;
});

const licenseLabel = computed(() =>
    draft.license === 'None' ? 'Unlicensed' : draft.license,
);

const badges = computed(() => {
    if (!draft.includeBadges) {
        return [];
    }

    const items: string[] = [];

    if (draft.enabled.license) {
        items.push(
            `![License](https://img.shields.io/badge/license-${encodeURIComponent(licenseLabel.value)}-blue.svg)`,
        );
    }

    if (githubRepository.value) {
        items.push(
            `![GitHub stars](https://img.shields.io/github/stars/${githubRepository.value}?style=flat)`,
        );
    }

    return items;
});

function normalizeUrl(rawUrl: string): string {
    const trimmed = rawUrl.trim();
    return /^https?:\/\//i.test(trimmed) ? trimmed : `https://${trimmed}`;
}

function formatRepositoryLink(rawUrl: string): string {
    const trimmed = rawUrl.trim();
    if (!trimmed) {
        return '—';
    }

    const normalized = normalizeUrl(trimmed);
    const gitMatch = trimmed.match(
        /(?:github\.com|gitlab\.com|bitbucket\.org)\/([^/]+)\/([^/?#]+)/i,
    );

    const title = gitMatch
        ? `${gitMatch[1]}/${gitMatch[2].replace(/\.git$/, '')}`
        : trimmed.replace(/^https?:\/\//i, '').replace(/\/+$/, '');

    return `[${title.replace(/\|/g, '\\|')}](${normalized})`;
}

function formatLinkCell(rawUrl: string): string {
    const trimmed = rawUrl.trim();
    if (!trimmed) {
        return '—';
    }

    const normalized = normalizeUrl(trimmed);
    const display = trimmed.replace(/^https?:\/\//i, '').replace(/\/+$/, '');
    const safeDisplay = display.replace(/\|/g, '\\|');

    return `[${safeDisplay}](${normalized})`;
}

const markdown = computed(() => {
    const blocks: string[] = [];
    const name = draft.name.trim() || 'Untitled project';

    blocks.push(`# ${name}`);

    if (draft.enabled.tagline && draft.tagline.trim()) {
        blocks.push(`> ${draft.tagline.trim()}`);
    }

    if (badges.value.length > 0) {
        blocks.push(badges.value.join(' '));
    }

    if (draft.enabled.description && draft.description.trim()) {
        blocks.push(draft.description.trim());
    }

    if (draft.enabled.features && features.value.length > 0) {
        blocks.push(
            [
                '## Features',
                '',
                ...features.value.map((feature) => `- ${feature}`),
            ].join('\n'),
        );
    }

    if (draft.enabled.techStack && selectedTechStack.value.length > 0) {
        blocks.push(
            [
                '## Tech stack',
                '',
                selectedTechStack.value
                    .map((technology) => getTechMarkdownBadge(technology))
                    .join(' '),
            ].join('\n'),
        );
    }

    const gettingStarted: string[] = [];

    if (draft.enabled.installation && draft.installation.trim()) {
        gettingStarted.push(
            [
                '### Installation',
                '',
                '```bash',
                draft.installation.trim(),
                '```',
            ].join('\n'),
        );
    }

    if (draft.enabled.usage && draft.usage.trim()) {
        gettingStarted.push(
            ['### Usage', '', '```bash', draft.usage.trim(), '```'].join('\n'),
        );
    }

    if (gettingStarted.length > 0) {
        blocks.push(['## Getting started', ...gettingStarted].join('\n\n'));
    }

    if (draft.enabled.links) {
        const populatedRows = draft.linkRows.filter(
            (row) =>
                row.repositoryUrl.trim() ||
                row.liveUrl.trim() ||
                row.portfolioUrl.trim(),
        );

        if (populatedRows.length > 0) {
            const tableLines = [
                '## Links',
                '',
                '| Repository | Live Project | Portfolio / Website |',
                '| :--- | :--- | :--- |',
                ...populatedRows.map(
                    (row) =>
                        `| ${formatRepositoryLink(row.repositoryUrl)} | ${formatLinkCell(row.liveUrl)} | ${formatLinkCell(row.portfolioUrl)} |`,
                ),
            ];
            blocks.push(tableLines.join('\n'));
        }
    }

    if (draft.enabled.license && draft.license !== 'None') {
        blocks.push(
            [
                '## License',
                '',
                `Released under the ${draft.license} license.`,
            ].join('\n'),
        );
    }

    blocks.push(
        '---\n\n_Special README was made with [MyDevPortal](https://github.com/jmdev11webdev/mydevportal)_',
    );

    return `${blocks.join('\n\n')}\n`;
});

const preview = computed(() => renderMarkdown(markdown.value));

onMounted(() => {
    const projectId = new URL(window.location.href).searchParams.get('project');

    if (
        projectId &&
        props.projects.some((project) => String(project.id) === projectId)
    ) {
        selectedProject.value = projectId;
        applyProject(projectId);

        return;
    }

    const saved = window.localStorage.getItem(storageKey);

    if (!saved) {
        return;
    }

    try {
        const parsed = JSON.parse(saved) as Partial<ReadmeForm>;
        Object.assign(draft, parsed);
        if (parsed.enabled) {
            Object.assign(draft.enabled, parsed.enabled);
        }
        if (Array.isArray(parsed.linkRows) && parsed.linkRows.length > 0) {
            draft.linkRows = parsed.linkRows.map((row) => ({
                repositoryUrl: row.repositoryUrl ?? '',
                liveUrl: row.liveUrl ?? '',
                portfolioUrl: row.portfolioUrl ?? '',
            }));
        }
    } catch {
        window.localStorage.removeItem(storageKey);
    }
});

watch(
    draft,
    (value) => {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(value));
        } catch {
            // Storage may be unavailable in private browsing; the generator still works.
        }
    },
    { deep: true },
);

function applyProject(projectId: string): void {
    const project = props.projects.find(
        (candidate) => String(candidate.id) === projectId,
    );

    if (!project) {
        return;
    }

    const preset = suitePresets[project.name.trim().toLowerCase()];

    draft.name = project.name;
    draft.tagline =
        preset?.tagline ??
        `${project.name} — built with ${(project.tech_stack ?? []).join(', ') || 'modern developer tooling'}.`;
    draft.description =
        project.description?.trim() ||
        preset?.description ||
        defaults.description;
    draft.features = preset?.features ?? defaultFeatures;
    draft.techStack =
        (project.tech_stack ?? []).length > 0
            ? (project.tech_stack ?? []).join(', ')
            : (preset?.techStack ?? '');
    draft.installation = preset?.installation ?? defaultInstallation;
    draft.usage = preset?.usage ?? defaultUsage;

    const repo =
        project.repository_url?.trim() ||
        preset?.linkRows[0]?.repositoryUrl ||
        '';
    const live = project.live_url?.trim() || preset?.linkRows[0]?.liveUrl || '';
    const portfolio = preset?.linkRows[0]?.portfolioUrl || '';

    draft.linkRows = [
        {
            repositoryUrl: repo,
            liveUrl: live,
            portfolioUrl: portfolio,
        },
    ];

    draft.license = preset?.license ?? 'MIT';
    draft.includeBadges = preset?.includeBadges ?? true;

    Object.assign(draft.enabled, {
        ...allEnabledFields,
        features: Boolean(draft.features.trim()),
        techStack: true,
        usage: Boolean(draft.usage.trim()),
        links: Boolean(repo || live || portfolio),
    });
}

function applyScratchDefaults(): void {
    Object.assign(draft, {
        ...defaults,
        linkRows: makeDefaultLinkRows(),
        enabled: { ...scratchEnabledDefaults },
    });
}

function onProjectChange(value: unknown): void {
    const next = String(value ?? scratchOption);
    selectedProject.value = next;

    if (next === scratchOption) {
        applyScratchDefaults();
        return;
    }

    applyProject(next);
}

function addLinkRow(): void {
    draft.linkRows.push({
        repositoryUrl: '',
        liveUrl: '',
        portfolioUrl: '',
    });
}

function removeLinkRow(index: number): void {
    if (draft.linkRows.length <= 1) {
        draft.linkRows[0] = {
            repositoryUrl: '',
            liveUrl: '',
            portfolioUrl: '',
        };
        return;
    }

    draft.linkRows.splice(index, 1);
}

let copiedTimer: number | undefined;
let copiedSourceTimer: number | undefined;

function legacyCopy(text: string): boolean {
    const field = document.createElement('textarea');
    field.value = text;
    field.setAttribute('readonly', '');
    field.style.cssText =
        'position:fixed;inset-block-start:0;inset-inline-start:0;opacity:0;pointer-events:none;';
    document.body.append(field);
    field.focus();
    field.select();
    field.setSelectionRange(0, field.value.length);

    try {
        return document.execCommand('copy');
    } finally {
        field.remove();
    }
}

async function writeClipboardText(text: string): Promise<void> {
    if (
        typeof window !== 'undefined' &&
        window.isSecureContext &&
        typeof navigator !== 'undefined' &&
        navigator.clipboard &&
        typeof navigator.clipboard.writeText === 'function'
    ) {
        try {
            await navigator.clipboard.writeText(text);
            return;
        } catch {
            // Fall through to execCommand fallback when Clipboard API is denied.
        }
    }

    if (!legacyCopy(text)) {
        throw new Error('Copy failed');
    }
}

async function copyMarkdown(): Promise<void> {
    try {
        await writeClipboardText(markdown.value);
        copied.value = true;
        toast.success('README copied to clipboard');

        window.clearTimeout(copiedTimer);
        copiedTimer = window.setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        toast.error('Could not copy the README to your clipboard');
    }
}

async function copySource(): Promise<void> {
    try {
        await writeClipboardText(markdown.value);
        copiedSource.value = true;
        toast.success('Markdown source copied');

        window.clearTimeout(copiedSourceTimer);
        copiedSourceTimer = window.setTimeout(() => {
            copiedSource.value = false;
        }, 2000);
    } catch {
        toast.error('Could not copy the markdown source');
    }
}

function downloadMarkdown(): void {
    const blob = new Blob([markdown.value], {
        type: 'text/markdown;charset=utf-8',
    });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = 'README.md';
    link.click();
    URL.revokeObjectURL(url);
}

function reset(): void {
    applyScratchDefaults();
    selectedProject.value = scratchOption;
    window.localStorage.removeItem(storageKey);
    toast.success('README generator reset');
}
</script>

<template>
    <Head title="README generator" />

    <div
        class="flex min-w-0 flex-1 flex-col gap-6 overflow-x-hidden p-4 md:p-6"
    >
        <PageHeader
            eyebrow="Ship"
            title="README generator"
            description="Turn a project into a polished README. Preview it live, copy the markdown, and drop it into your repository."
            :icon="FileCode"
        >
            <template #actions>
                <div
                    class="grid w-full grid-cols-3 gap-2 sm:flex sm:w-auto sm:items-center"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="w-full sm:w-auto"
                        @click="reset"
                    >
                        <RotateCcw class="size-4" />
                        <span>Reset</span>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="w-full sm:w-auto"
                        @click="downloadMarkdown"
                    >
                        <Download class="size-4" />
                        <span>Download</span>
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        class="w-full sm:w-auto"
                        @click="copyMarkdown"
                    >
                        <Check v-if="copied" class="size-4" />
                        <Copy v-else class="size-4" />
                        <span class="truncate">{{
                            copied ? 'Copied' : 'Copy'
                        }}</span>
                    </Button>
                </div>
            </template>
        </PageHeader>

        <div
            class="grid min-w-0 gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"
        >
            <div
                class="min-w-0 space-y-5 rounded-2xl border bg-card p-4 shadow-sm sm:p-5"
            >
                <div class="grid gap-2">
                    <Label for="project">Start from a project</Label>
                    <Select
                        :model-value="selectedProject"
                        @update:model-value="onProjectChange"
                    >
                        <SelectTrigger id="project" class="w-full">
                            <SelectValue placeholder="Start from scratch" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="scratchOption">
                                Start from scratch
                            </SelectItem>
                            <SelectItem
                                v-for="project in projects"
                                :key="project.id"
                                :value="String(project.id)"
                            >
                                {{ project.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        Pull consistent, connected presets for Orbit, Pulse API,
                        Devfolio, or CLI Toolkit — or start from scratch and
                        toggle fields below.
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="name">Project name</Label>
                    <Input id="name" v-model="draft.name" placeholder="Orbit" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="tagline">Tagline</Label>
                        <label
                            class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground"
                        >
                            <Checkbox v-model="draft.enabled.tagline" />
                            <span>Enable</span>
                        </label>
                    </div>
                    <Input
                        id="tagline"
                        v-model="draft.tagline"
                        :disabled="!draft.enabled.tagline"
                        placeholder="A one-line description of the project"
                    />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="description">Description</Label>
                        <label
                            class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground"
                        >
                            <Checkbox v-model="draft.enabled.description" />
                            <span>Enable</span>
                        </label>
                    </div>
                    <Textarea
                        id="description"
                        v-model="draft.description"
                        :disabled="!draft.enabled.description"
                        placeholder="What does this project do, and who is it for?"
                    />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="features">Features</Label>
                        <label
                            class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground"
                        >
                            <Checkbox v-model="draft.enabled.features" />
                            <span>Enable</span>
                        </label>
                    </div>
                    <Textarea
                        id="features"
                        v-model="draft.features"
                        :disabled="!draft.enabled.features"
                        placeholder="One feature per line"
                        class="min-h-28"
                    />
                    <p class="text-xs text-muted-foreground">
                        One feature per line. Each becomes a bullet in the
                        README.
                    </p>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="tech-stack">Tech stack</Label>
                        <label
                            class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground"
                        >
                            <Checkbox v-model="draft.enabled.techStack" />
                            <span>Enable</span>
                        </label>
                    </div>
                    <TechStackSelector
                        id="tech-stack"
                        v-model="selectedTechStack"
                        :disabled="!draft.enabled.techStack"
                        placeholder="Select stack from Devicon..."
                    />
                    <p class="text-xs text-muted-foreground">
                        Selected tools render with their icon and adaptive brand
                        background in both preview and markdown output.
                    </p>
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="installation">Installation</Label>
                        <label
                            class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground"
                        >
                            <Checkbox v-model="draft.enabled.installation" />
                            <span>Enable</span>
                        </label>
                    </div>
                    <Textarea
                        id="installation"
                        v-model="draft.installation"
                        :disabled="!draft.enabled.installation"
                        class="min-h-36 font-mono text-xs"
                    />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <Label for="usage">Usage</Label>
                        <label
                            class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground"
                        >
                            <Checkbox v-model="draft.enabled.usage" />
                            <span>Enable</span>
                        </label>
                    </div>
                    <Textarea
                        id="usage"
                        v-model="draft.usage"
                        :disabled="!draft.enabled.usage"
                        placeholder="Optional code sample showing how to use the project"
                        class="min-h-24 font-mono text-xs"
                    />
                </div>

                <!-- 3-Column Links Table -->
                <div class="grid gap-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-medium">
                            Project links table
                        </span>
                        <div class="flex items-center gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-7 px-2 text-xs"
                                :disabled="!draft.enabled.links"
                                @click="addLinkRow"
                            >
                                <Plus class="size-3.5" />
                                Add row
                            </Button>
                            <label
                                class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground"
                            >
                                <Checkbox v-model="draft.enabled.links" />
                                <span>Enable</span>
                            </label>
                        </div>
                    </div>

                    <div
                        class="overflow-x-auto rounded-lg border border-border"
                        :class="draft.enabled.links ? '' : 'opacity-60'"
                    >
                        <table class="w-full min-w-[560px] text-left text-xs">
                            <thead>
                                <tr
                                    class="border-b border-border bg-muted/60 text-[11px] font-semibold text-muted-foreground"
                                >
                                    <th class="px-2.5 py-2">Repository URL</th>
                                    <th class="px-2.5 py-2">Live Project</th>
                                    <th class="px-2.5 py-2">
                                        Portfolio / Website
                                    </th>
                                    <th class="w-9 px-1.5 py-2 text-center">
                                        <span class="sr-only">Remove row</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr
                                    v-for="(row, index) in draft.linkRows"
                                    :key="index"
                                >
                                    <td class="p-1.5">
                                        <Input
                                            v-model="row.repositoryUrl"
                                            :disabled="!draft.enabled.links"
                                            :placeholder="repositoryPlaceholder"
                                            aria-label="Repository URL"
                                            class="h-8 text-xs"
                                        />
                                    </td>
                                    <td class="p-1.5">
                                        <Input
                                            v-model="row.liveUrl"
                                            :disabled="!draft.enabled.links"
                                            :placeholder="liveUrlPlaceholder"
                                            aria-label="Live Project URL"
                                            class="h-8 text-xs"
                                        />
                                    </td>
                                    <td class="p-1.5">
                                        <Input
                                            v-model="row.portfolioUrl"
                                            :disabled="!draft.enabled.links"
                                            :placeholder="portfolioPlaceholder"
                                            aria-label="Portfolio or Website URL"
                                            class="h-8 text-xs"
                                        />
                                    </td>
                                    <td class="p-1.5 text-center">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            class="size-7 text-muted-foreground hover:text-destructive"
                                            :disabled="!draft.enabled.links"
                                            aria-label="Remove link row"
                                            @click="removeLinkRow(index)"
                                        >
                                            <Trash2 class="size-3.5" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <div class="flex items-center justify-between gap-2">
                            <Label for="license">License</Label>
                            <label
                                class="flex cursor-pointer items-center gap-2 text-xs text-muted-foreground"
                            >
                                <Checkbox v-model="draft.enabled.license" />
                                <span>Enable</span>
                            </label>
                        </div>
                        <Select
                            v-model="draft.license"
                            :disabled="!draft.enabled.license"
                        >
                            <SelectTrigger id="license" class="w-full">
                                <SelectValue placeholder="Select license" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in licenseOptions"
                                    :key="option"
                                    :value="option"
                                >
                                    {{ option }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <label
                        for="badges"
                        class="flex cursor-pointer items-end gap-2 pb-2 text-sm"
                    >
                        <Checkbox id="badges" v-model="draft.includeBadges" />
                        <span>Include shields.io badges</span>
                    </label>
                </div>
            </div>

            <div class="min-w-0 xl:sticky xl:top-6 xl:self-start">
                <div
                    class="overflow-hidden rounded-2xl border bg-card shadow-sm"
                >
                    <div
                        class="flex items-center justify-between gap-3 border-b px-5 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <Sparkles class="size-4 text-indigo-500" />
                            <p class="text-sm font-medium">Live preview</p>
                        </div>
                        <span class="text-xs text-muted-foreground">
                            {{ markdown.length.toLocaleString() }} characters
                        </span>
                    </div>

                    <div
                        class="readme-preview max-h-[68vh] overflow-auto p-6 text-sm text-foreground"
                        v-html="preview"
                    />
                </div>

                <details
                    class="group mt-4 overflow-hidden rounded-2xl border bg-card shadow-sm"
                >
                    <summary
                        class="flex cursor-pointer items-center justify-between gap-3 px-5 py-3 text-sm font-medium"
                    >
                        <span>Markdown source</span>
                        <span class="text-xs text-muted-foreground">
                            Click to expand
                        </span>
                    </summary>

                    <div class="relative border-t">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="absolute top-3 right-3"
                            @click="copySource"
                        >
                            <Check v-if="copiedSource" class="size-3.5" />
                            <Copy v-else class="size-3.5" />
                            {{ copiedSource ? 'Copied' : 'Copy' }}
                        </Button>
                        <pre
                            class="max-h-80 overflow-auto p-4 pr-24 text-xs leading-relaxed"
                            >{{ markdown }}</pre>
                    </div>
                </details>
            </div>
        </div>
    </div>
</template>
