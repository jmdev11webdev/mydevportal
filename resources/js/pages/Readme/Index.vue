<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    Check,
    Copy,
    Download,
    FileCode,
    RotateCcw,
    Sparkles,
} from '@lucide/vue';
import { toast } from 'vue-sonner';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { renderMarkdown } from '@/lib/markdown';
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

type ReadmeForm = {
    name: string;
    tagline: string;
    description: string;
    features: string;
    techStack: string;
    installation: string;
    usage: string;
    repositoryUrl: string;
    liveUrl: string;
    license: License;
    includeBadges: boolean;
};

const storageKey = 'mydevportal.readme-generator';
const repositoryPlaceholder = 'https://github.com/you/project';
const liveUrlPlaceholder = 'https://project.example.com';

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

const defaults: ReadmeForm = {
    name: '',
    tagline: 'A short, honest description of what this project does.',
    description: '',
    features: '',
    techStack: '',
    installation: defaultInstallation,
    usage: '',
    repositoryUrl: '',
    liveUrl: '',
    license: 'MIT',
    includeBadges: true,
};

const draft = reactive<ReadmeForm>({ ...defaults });
const selectedProject = ref('');
const copied = ref(false);
const copiedSource = ref(false);

const licenseOptions: License[] = [
    'MIT',
    'Apache-2.0',
    'GPL-3.0',
    'BSD-3-Clause',
    'None',
];

const features = computed(() =>
    draft.features
        .split('\n')
        .map((feature) => feature.trim())
        .filter((feature) => feature.length > 0),
);

const techStack = computed(() =>
    draft.techStack
        .split(',')
        .map((technology) => technology.trim())
        .filter((technology) => technology.length > 0),
);

const githubRepository = computed(() => {
    const match = draft.repositoryUrl
        .trim()
        .match(/github\.com\/([^/]+)\/([^/?#]+)/i);

    if (!match) {
        return null;
    }

    return `${match[1]}/${match[2].replace(/\.git$/, '')}`;
});

const licenseLabel = computed(() =>
    draft.license === 'None' ? 'Unlicensed' : draft.license,
);

const badges = computed(() => {
    if (!draft.includeBadges) {
        return [];
    }

    const items = [
        `![License](https://img.shields.io/badge/license-${encodeURIComponent(licenseLabel.value)}-blue.svg)`,
    ];

    if (githubRepository.value) {
        items.push(
            `![GitHub stars](https://img.shields.io/github/stars/${githubRepository.value}?style=flat)`,
        );
    }

    return items;
});

const markdown = computed(() => {
    const blocks: string[] = [];
    const name = draft.name.trim() || 'Untitled project';

    blocks.push(`# ${name}`);

    if (draft.tagline.trim()) {
        blocks.push(`> ${draft.tagline.trim()}`);
    }

    if (badges.value.length > 0) {
        blocks.push(badges.value.join(' '));
    }

    if (draft.description.trim()) {
        blocks.push(draft.description.trim());
    }

    if (features.value.length > 0) {
        blocks.push(
            '## Features',
            '',
            ...features.value.map((feature) => `- ${feature}`),
        );
    }

    if (techStack.value.length > 0) {
        blocks.push(
            '## Tech stack',
            '',
            techStack.value
                .map((technology) => `\`${technology}\``)
                .join(' · '),
        );
    }

    const gettingStarted = ['## Getting started'];

    if (draft.installation.trim()) {
        gettingStarted.push(
            '',
            '### Installation',
            '',
            '```bash',
            draft.installation.trim(),
            '```',
        );
    }

    if (draft.usage.trim()) {
        gettingStarted.push(
            '',
            '### Usage',
            '',
            '```',
            draft.usage.trim(),
            '```',
        );
    }

    if (gettingStarted.length > 1) {
        blocks.push(...gettingStarted);
    }

    const links: string[] = [];

    if (draft.repositoryUrl.trim()) {
        links.push(`- [Repository](${draft.repositoryUrl.trim()})`);
    }

    if (draft.liveUrl.trim()) {
        links.push(`- [Live demo](${draft.liveUrl.trim()})`);
    }

    if (links.length > 0) {
        blocks.push('## Links', '', ...links);
    }

    if (draft.license !== 'None') {
        blocks.push(
            '## License',
            '',
            `Released under the ${draft.license} license.`,
        );
    }

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
        Object.assign(draft, JSON.parse(saved) as Partial<ReadmeForm>);
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

    draft.name = project.name;
    draft.description = project.description ?? '';
    draft.techStack = (project.tech_stack ?? []).join(', ');
    draft.repositoryUrl = project.repository_url ?? '';
    draft.liveUrl = project.live_url ?? '';
}

function onProjectChange(): void {
    if (selectedProject.value) {
        applyProject(selectedProject.value);
    }
}

async function copyMarkdown(): Promise<void> {
    try {
        await navigator.clipboard.writeText(markdown.value);
        copied.value = true;
        toast.success('README copied to clipboard');

        window.setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch {
        toast.error('Could not copy the README to your clipboard');
    }
}

async function copySource(): Promise<void> {
    try {
        await navigator.clipboard.writeText(markdown.value);
        copiedSource.value = true;
        toast.success('Markdown source copied');

        window.setTimeout(() => {
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
    Object.assign(draft, defaults);
    selectedProject.value = '';
    window.localStorage.removeItem(storageKey);
    toast.success('README generator reset');
}
</script>

<template>
    <Head title="README generator" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Ship"
            title="README generator"
            description="Turn a project into a polished README. Preview it live, copy the markdown, and drop it into your repository."
            :icon="FileCode"
        >
            <template #actions>
                <Button variant="outline" @click="reset">
                    <RotateCcw class="size-4" />
                    Reset
                </Button>
                <Button variant="outline" @click="downloadMarkdown">
                    <Download class="size-4" />
                    Download
                </Button>
                <Button @click="copyMarkdown">
                    <Check v-if="copied" class="size-4" />
                    <Copy v-else class="size-4" />
                    {{ copied ? 'Copied' : 'Copy markdown' }}
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div class="space-y-5 rounded-2xl border bg-card p-5 shadow-sm">
                <div class="grid gap-2">
                    <Label for="project">Start from a project</Label>
                    <select
                        id="project"
                        v-model="selectedProject"
                        class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        @change="onProjectChange"
                    >
                        <option value="">Start from scratch</option>
                        <option
                            v-for="project in projects"
                            :key="project.id"
                            :value="String(project.id)"
                        >
                            {{ project.name }}
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground">
                        Pull the name, description, stack and links from an
                        existing project.
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="name">Project name</Label>
                    <Input id="name" v-model="draft.name" placeholder="Orbit" />
                </div>

                <div class="grid gap-2">
                    <Label for="tagline">Tagline</Label>
                    <Input
                        id="tagline"
                        v-model="draft.tagline"
                        placeholder="A one-line description of the project"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        v-model="draft.description"
                        placeholder="What does this project do, and who is it for?"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="features">Features</Label>
                    <Textarea
                        id="features"
                        v-model="draft.features"
                        placeholder="One feature per line"
                        class="min-h-28"
                    />
                    <p class="text-xs text-muted-foreground">
                        One feature per line. Each becomes a bullet in the
                        README.
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="tech-stack">Tech stack</Label>
                    <Input
                        id="tech-stack"
                        v-model="draft.techStack"
                        placeholder="Laravel, Vue 3, TypeScript, SQLite"
                    />
                    <p class="text-xs text-muted-foreground">
                        Comma-separated. Rendered as inline code in the README.
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="installation">Installation</Label>
                    <Textarea
                        id="installation"
                        v-model="draft.installation"
                        class="min-h-36 font-mono text-xs"
                    />
                </div>

                <div class="grid gap-2">
                    <Label for="usage">Usage</Label>
                    <Textarea
                        id="usage"
                        v-model="draft.usage"
                        placeholder="Optional code sample showing how to use the project"
                        class="min-h-24 font-mono text-xs"
                    />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="repository">Repository URL</Label>
                        <Input
                            id="repository"
                            v-model="draft.repositoryUrl"
                            :placeholder="repositoryPlaceholder"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="live-url">Live URL</Label>
                        <Input
                            id="live-url"
                            v-model="draft.liveUrl"
                            :placeholder="liveUrlPlaceholder"
                        />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="license">License</Label>
                        <select
                            id="license"
                            v-model="draft.license"
                            class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option
                                v-for="option in licenseOptions"
                                :key="option"
                                :value="option"
                            >
                                {{ option }}
                            </option>
                        </select>
                    </div>

                    <label
                        for="badges"
                        class="flex items-end gap-2 pb-2 text-sm"
                    >
                        <Checkbox id="badges" v-model="draft.includeBadges" />
                        <span>Include shields.io badges</span>
                    </label>
                </div>
            </div>

            <div class="xl:sticky xl:top-6 xl:self-start">
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

<style scoped>
.readme-preview :deep(h1) {
    margin: 0 0 1rem;
    font-size: 1.75rem;
    font-weight: 700;
    letter-spacing: -0.03em;
}

.readme-preview :deep(h2) {
    margin: 1.75rem 0 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: -0.02em;
}

.readme-preview :deep(h3) {
    margin: 1.25rem 0 0.5rem;
    font-size: 1rem;
    font-weight: 600;
}

.readme-preview :deep(p) {
    margin: 0.75rem 0;
    line-height: 1.7;
}

.readme-preview :deep(ul) {
    margin: 0.75rem 0;
    padding-left: 1.25rem;
    list-style: disc;
}

.readme-preview :deep(ol) {
    margin: 0.75rem 0;
    padding-left: 1.25rem;
    list-style: decimal;
}

.readme-preview :deep(li) {
    margin: 0.3rem 0;
    line-height: 1.6;
}

.readme-preview :deep(a) {
    color: #6366f1;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.readme-preview :deep(code) {
    border-radius: 0.375rem;
    background: rgb(127 127 127 / 0.15);
    padding: 0.15rem 0.35rem;
    font-size: 0.85em;
}

.readme-preview :deep(pre) {
    margin: 1rem 0;
    overflow-x: auto;
    border-radius: 0.75rem;
    background: #0f172a;
    padding: 1rem;
    color: #e2e8f0;
}

.readme-preview :deep(pre code) {
    background: transparent;
    padding: 0;
    color: inherit;
}

.readme-preview :deep(blockquote) {
    margin: 1rem 0;
    border-left: 3px solid rgb(127 127 127 / 0.4);
    padding-left: 1rem;
    color: rgb(127 127 127);
}

.readme-preview :deep(img) {
    display: inline-block;
    margin-right: 0.25rem;
}

.readme-preview :deep(hr) {
    margin: 1.5rem 0;
    border-color: rgb(127 127 127 / 0.25);
}
</style>
