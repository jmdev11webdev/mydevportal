<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    Command,
    Download,
    Flame,
    FolderGit2,
    GraduationCap,
    ListChecks,
    Lock,
    NotebookPen,
    ShieldCheck,
    Sparkles,
    Target,
    WifiOff,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

const features = [
    {
        icon: FolderGit2,
        title: 'Projects that stay honest',
        description:
            'Track builds, stack decisions and real task progress without another spreadsheet.',
    },
    {
        icon: ListChecks,
        title: 'A focus board that moves',
        description:
            'Capture, prioritise and ship. A three-column board with zero ceremony.',
    },
    {
        icon: GraduationCap,
        title: 'Learning with momentum',
        description:
            'Courses, books and docs in one curriculum, with progress you can actually see.',
    },
    {
        icon: NotebookPen,
        title: 'Private notes & snippets',
        description:
            'A scratchpad for decisions, commands and half-formed ideas — stored for your eyes only.',
    },
    {
        icon: Target,
        title: 'Career, on your terms',
        description:
            'Goals, applications, certifications and wins in one quiet timeline.',
    },
    {
        icon: WifiOff,
        title: 'Installable & offline-ready',
        description:
            'Add MyDevPortal to your home screen. It behaves like an app, not a tab.',
    },
];

const privacyPoints = [
    'No third-party analytics or ad trackers',
    'Your notes and career data stay in your own workspace',
    'Passwordless passkeys and two-factor authentication built in',
    'Open source and self-hostable when you want full control',
];
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-[#070b12] text-slate-100">
        <div class="relative overflow-hidden">
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.06]"
                style="
                    background-image:
                        linear-gradient(to right, white 1px, transparent 1px),
                        linear-gradient(to bottom, white 1px, transparent 1px);
                    background-size: 48px 48px;
                "
            />
            <div
                class="pointer-events-none absolute -top-40 left-1/2 size-[640px] -translate-x-1/2 rounded-full bg-indigo-600/20 blur-3xl"
            />
            <div
                class="pointer-events-none absolute top-40 -right-20 size-96 rounded-full bg-emerald-500/10 blur-3xl"
            />

            <header
                class="relative z-20 mx-auto flex w-full max-w-6xl items-center justify-between px-5 py-5"
            >
                <Link href="/" class="flex items-center gap-2.5">
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-white text-slate-900"
                    >
                        <AppLogoIcon class="size-5" />
                    </span>
                    <span class="text-sm font-semibold tracking-tight">
                        MyDevPortal
                    </span>
                </Link>

                <nav
                    class="hidden items-center gap-7 text-sm text-slate-400 md:flex"
                >
                    <a href="#features" class="transition hover:text-white"
                        >Features</a
                    >
                    <a href="#privacy" class="transition hover:text-white"
                        >Privacy</a
                    >
                    <a href="#pwa" class="transition hover:text-white">PWA</a>
                </nav>

                <div class="flex items-center gap-2">
                    <template v-if="isAuthenticated">
                        <Link
                            href="/dashboard"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-900 transition hover:bg-slate-200"
                        >
                            Open dashboard
                            <ArrowRight class="size-4" />
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            href="/login"
                            class="hidden rounded-lg px-4 py-2 text-sm text-slate-300 transition hover:bg-white/5 hover:text-white sm:inline-flex"
                        >
                            Log in
                        </Link>
                        <Link
                            href="/register"
                            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-900 transition hover:bg-slate-200"
                        >
                            Get started
                            <ArrowRight class="size-4" />
                        </Link>
                    </template>
                </div>
            </header>

            <main
                class="relative z-10 mx-auto grid w-full max-w-6xl items-center gap-12 px-5 pt-12 pb-20 lg:grid-cols-[1.05fr_0.95fr] lg:pt-20"
            >
                <div>
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium tracking-wide text-slate-300 shadow-sm"
                    >
                        <Sparkles class="size-3.5 text-emerald-400" />
                        Privacy-first developer workspace
                    </div>

                    <h1
                        class="mt-6 text-4xl leading-[1.06] font-semibold tracking-tight sm:text-5xl lg:text-6xl"
                    >
                        Your private command centre for the
                        <span
                            class="bg-gradient-to-r from-indigo-300 via-sky-300 to-emerald-300 bg-clip-text text-transparent"
                        >
                            developer career
                        </span>
                        .
                    </h1>

                    <p
                        class="mt-5 max-w-xl text-base leading-relaxed text-slate-400"
                    >
                        MyDevPortal brings your projects, tasks, learning and
                        career into one calm, installable workspace — built for
                        software engineers who would rather ship than manage
                        tools.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            :href="isAuthenticated ? '/dashboard' : '/demo'"
                            class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-black/20 transition hover:bg-slate-200"
                        >
                            {{
                                isAuthenticated
                                    ? 'Open dashboard'
                                    : 'Explore the live demo'
                            }}
                            <ArrowRight class="size-4" />
                        </Link>
                        <Link
                            v-if="!isAuthenticated"
                            href="/register"
                            class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-medium text-white transition hover:bg-white/10"
                        >
                            Create a free workspace
                        </Link>
                    </div>

                    <div
                        class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-500"
                    >
                        <span class="flex items-center gap-1.5">
                            <Lock class="size-3.5 text-emerald-400" />
                            No trackers. No ads.
                        </span>
                        <span class="flex items-center gap-1.5">
                            <ShieldCheck class="size-3.5 text-emerald-400" />
                            Passkeys + 2FA
                        </span>
                        <span class="flex items-center gap-1.5">
                            <Command class="size-3.5 text-emerald-400" />
                            Keyboard-friendly
                        </span>
                    </div>
                </div>

                <div class="relative">
                    <div
                        class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-indigo-500/20 via-transparent to-emerald-500/10 blur-2xl"
                    />
                    <div
                        class="relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/80 shadow-2xl shadow-black/40 backdrop-blur"
                    >
                        <div
                            class="flex items-center justify-between border-b border-white/10 px-4 py-3"
                        >
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="size-2.5 rounded-full bg-rose-400/80"
                                />
                                <span
                                    class="size-2.5 rounded-full bg-amber-400/80"
                                />
                                <span
                                    class="size-2.5 rounded-full bg-emerald-400/80"
                                />
                            </div>
                            <span class="text-[10px] text-slate-500"
                                >app.mydevportal.local</span
                            >
                            <span
                                class="rounded-md border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[9px] font-medium text-emerald-300"
                            >
                                Private
                            </span>
                        </div>

                        <div class="p-5">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p
                                        class="text-[10px] tracking-[0.16em] text-slate-500 uppercase"
                                    >
                                        Good morning, Alex
                                    </p>
                                    <p class="mt-1 text-lg font-semibold">
                                        Ship the ingestion spec
                                    </p>
                                </div>
                                <div
                                    class="flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-2.5 py-1.5 text-[10px]"
                                >
                                    <Flame class="size-3.5 text-amber-400" />
                                    12-day streak
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-3 gap-2">
                                <div
                                    class="rounded-xl border border-white/10 bg-white/[0.03] p-3"
                                >
                                    <p
                                        class="text-[9px] text-slate-500 uppercase"
                                    >
                                        Open tasks
                                    </p>
                                    <p class="mt-1 text-xl font-semibold">8</p>
                                </div>
                                <div
                                    class="rounded-xl border border-white/10 bg-white/[0.03] p-3"
                                >
                                    <p
                                        class="text-[9px] text-slate-500 uppercase"
                                    >
                                        Done this week
                                    </p>
                                    <p class="mt-1 text-xl font-semibold">14</p>
                                </div>
                                <div
                                    class="rounded-xl border border-white/10 bg-white/[0.03] p-3"
                                >
                                    <p
                                        class="text-[9px] text-slate-500 uppercase"
                                    >
                                        Projects
                                    </p>
                                    <p class="mt-1 text-xl font-semibold">3</p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <div
                                    v-for="task in [
                                        {
                                            title: 'Polish onboarding empty states',
                                            project: 'Orbit',
                                            done: false,
                                        },
                                        {
                                            title: 'Add rate limiting to webhooks',
                                            project: 'Pulse API',
                                            done: false,
                                        },
                                        {
                                            title: 'Write the weekly changelog',
                                            project: 'CLI Toolkit',
                                            done: false,
                                        },
                                    ]"
                                    :key="task.title"
                                    class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5"
                                >
                                    <span
                                        class="flex size-4 items-center justify-center rounded-full border border-slate-600"
                                    />
                                    <span
                                        class="flex-1 text-xs text-slate-200"
                                        >{{ task.title }}</span
                                    >
                                    <span
                                        class="rounded-md bg-white/5 px-1.5 py-0.5 text-[9px] text-slate-400"
                                        >{{ task.project }}</span
                                    >
                                </div>
                            </div>

                            <div class="mt-4">
                                <div
                                    class="mb-1.5 flex items-center justify-between text-[10px]"
                                >
                                    <span class="text-slate-400"
                                        >Frontend Masters · TypeScript</span
                                    >
                                    <span class="text-slate-300">62%</span>
                                </div>
                                <div
                                    class="h-1.5 overflow-hidden rounded-full bg-white/10"
                                >
                                    <div
                                        class="h-full w-[62%] rounded-full bg-gradient-to-r from-indigo-500 to-sky-400"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <section id="features" class="border-t border-white/5 bg-[#080d16]">
            <div class="mx-auto w-full max-w-6xl px-5 py-20">
                <div class="max-w-2xl">
                    <p
                        class="text-[11px] font-semibold tracking-[0.18em] text-indigo-300 uppercase"
                    >
                        One workspace, the whole loop
                    </p>
                    <h2
                        class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl"
                    >
                        Everything you need between "idea" and "shipped".
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-400">
                        MyDevPortal is opinionated about the things that matter
                        and quiet about the rest. No dashboards for the sake of
                        dashboards.
                    </p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="group rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:border-white/20 hover:bg-white/[0.05]"
                    >
                        <div
                            class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500/20 to-emerald-500/10 text-indigo-300"
                        >
                            <component :is="feature.icon" class="size-5" />
                        </div>
                        <h3 class="mt-4 text-sm font-semibold">
                            {{ feature.title }}
                        </h3>
                        <p
                            class="mt-1.5 text-xs leading-relaxed text-slate-400"
                        >
                            {{ feature.description }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section id="privacy" class="border-t border-white/5">
            <div
                class="mx-auto grid w-full max-w-6xl gap-10 px-5 py-20 lg:grid-cols-2 lg:items-center"
            >
                <div>
                    <p
                        class="text-[11px] font-semibold tracking-[0.18em] text-emerald-300 uppercase"
                    >
                        Privacy is the product
                    </p>
                    <h2
                        class="mt-3 text-3xl font-semibold tracking-tight sm:text-4xl"
                    >
                        Your career data is not a growth metric.
                    </h2>
                    <p
                        class="mt-3 max-w-lg text-sm leading-relaxed text-slate-400"
                    >
                        Notes, applications and goals are some of the most
                        personal things a developer keeps. MyDevPortal treats
                        them that way — private by default, yours to export, and
                        never sold.
                    </p>

                    <ul class="mt-6 space-y-3">
                        <li
                            v-for="point in privacyPoints"
                            :key="point"
                            class="flex items-start gap-3 text-sm text-slate-300"
                        >
                            <span
                                class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-300"
                            >
                                <Check class="size-3" />
                            </span>
                            {{ point }}
                        </li>
                    </ul>
                </div>

                <div class="relative">
                    <div
                        class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-xl"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex size-8 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300"
                                >
                                    <NotebookPen class="size-4" />
                                </span>
                                <div>
                                    <p class="text-sm font-semibold">
                                        Interview prep: system design
                                    </p>
                                    <p class="text-[10px] text-slate-500">
                                        Private note · updated today
                                    </p>
                                </div>
                            </div>
                            <Lock class="size-4 text-emerald-400" />
                        </div>
                        <div
                            class="mt-4 space-y-2 rounded-xl border border-white/10 bg-black/20 p-4 font-mono text-[11px] leading-relaxed text-slate-400"
                        >
                            <p>1. Design a URL shortener with analytics.</p>
                            <p>
                                2. Design a notification pipeline with retries.
                            </p>
                            <p>3. Design a multi-tenant audit log.</p>
                            <p class="text-slate-600">
                                Focus on trade-offs, not perfect answers.
                            </p>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-1.5">
                            <span
                                class="rounded-md border border-white/10 bg-white/5 px-2 py-0.5 text-[10px] text-slate-400"
                                >#career</span
                            >
                            <span
                                class="rounded-md border border-white/10 bg-white/5 px-2 py-0.5 text-[10px] text-slate-400"
                                >#system-design</span
                            >
                        </div>
                    </div>
                    <div
                        class="absolute -bottom-5 -left-4 flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-3 py-2 text-[11px] text-emerald-200 backdrop-blur"
                    >
                        <ShieldCheck class="size-4" />
                        Encrypted account · passkey ready
                    </div>
                </div>
            </div>
        </section>

        <section id="pwa" class="border-t border-white/5 bg-[#080d16]">
            <div class="mx-auto w-full max-w-6xl px-5 py-20">
                <div
                    class="grid gap-8 rounded-3xl border border-white/10 bg-gradient-to-br from-indigo-950/60 via-slate-900/60 to-emerald-950/40 p-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center lg:p-12"
                >
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] text-slate-300"
                        >
                            <Download class="size-3.5 text-emerald-300" />
                            Installable PWA
                        </div>
                        <h2
                            class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl"
                        >
                            Feels like an app. Lives in your browser.
                        </h2>
                        <p
                            class="mt-3 max-w-lg text-sm leading-relaxed text-slate-400"
                        >
                            Install MyDevPortal on desktop or mobile, keep your
                            workspace one tap away, and stay productive when the
                            connection drops.
                        </p>
                        <Link
                            :href="isAuthenticated ? '/dashboard' : '/demo'"
                            class="mt-6 inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-200"
                        >
                            Try it now
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="item in [
                                {
                                    icon: Download,
                                    title: 'Install anywhere',
                                    body: 'Desktop, Android and iOS home screen.',
                                },
                                {
                                    icon: WifiOff,
                                    title: 'Offline shell',
                                    body: 'The app opens even with no signal.',
                                },
                                {
                                    icon: Command,
                                    title: 'Keyboard first',
                                    body: 'Fast capture without leaving the flow.',
                                },
                                {
                                    icon: Lock,
                                    title: 'Private session',
                                    body: 'Your workspace requires your account.',
                                },
                            ]"
                            :key="item.title"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                        >
                            <component
                                :is="item.icon"
                                class="size-4 text-indigo-300"
                            />
                            <p class="mt-2 text-xs font-semibold">
                                {{ item.title }}
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                {{ item.body }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-t border-white/5">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col items-start justify-between gap-6 px-5 py-16 sm:flex-row sm:items-center"
            >
                <div>
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Ready when you are.
                    </h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Start with a demo workspace, or make it yours in under a
                        minute.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link
                        v-if="!isAuthenticated"
                        href="/demo"
                        class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/5 px-5 py-3 text-sm font-medium transition hover:bg-white/10"
                    >
                        Explore the demo
                    </Link>
                    <Link
                        :href="isAuthenticated ? '/dashboard' : '/register'"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-200"
                    >
                        {{
                            isAuthenticated
                                ? 'Open dashboard'
                                : 'Create free workspace'
                        }}
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>
        </section>

        <footer class="border-t border-white/5">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col items-start justify-between gap-4 px-5 py-8 text-xs text-slate-500 sm:flex-row sm:items-center"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="flex size-6 items-center justify-center rounded-md bg-white text-slate-900"
                    >
                        <AppLogoIcon class="size-3.5" />
                    </span>
                    <span>MyDevPortal · built with Laravel</span>
                </div>
                <div class="flex items-center gap-5">
                    <a href="#features" class="transition hover:text-slate-300"
                        >Features</a
                    >
                    <a href="#privacy" class="transition hover:text-slate-300"
                        >Privacy</a
                    >
                    <Link href="/login" class="transition hover:text-slate-300"
                        >Log in</Link
                    >
                </div>
            </div>
        </footer>
    </div>
</template>
