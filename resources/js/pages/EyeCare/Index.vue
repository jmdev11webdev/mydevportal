<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    Activity,
    ArrowUpRight,
    Bell,
    BookOpenCheck,
    CheckCircle2,
    Clock,
    Compass,
    Eye,
    Flame,
    Hourglass,
    Pause,
    Play,
    RefreshCw,
    RotateCcw,
    ShieldAlert,
    SkipForward,
    Sparkles,
    Square,
    Timer,
    Volume2,
    VolumeX,
} from '@lucide/vue';
import PageHeader from '@/components/PageHeader.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import StatCard from '@/components/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useEyeCareTimer } from '@/composables/useEyeCareTimer';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Eye Care', href: '/eye-care' }],
    },
});

const {
    phase,
    isRunning,
    remainingSeconds,
    progressPercent,
    formattedTime,
    cyclesToday,
    totalCycles,
    focusMinutesToday,
    soundEnabled,
    autoCycle,
    restOverlayEnabled,
    isDefaultState,
    sources,
    toggleTimer,
    stopTimer,
    triggerRestNow,
    skipRest,
    resetDailyStats,
    // Reciprocal Custom Duration Timer
    durationTargetMinutes,
    durationRunning,
    syncDurationWithEyeCare,
    durationLabel,
    isDurationPausedByEyeBreak,
    durationProgressPercent,
    formattedDurationRemaining,
    formattedDurationElapsed,
    isDurationDefaultState,
    setDurationMinutes,
    toggleDurationTimer,
    stopDurationTimer,
} = useEyeCareTimer();

const customMinutesInput = ref(durationTargetMinutes.value);
const sessionLabelInput = ref(durationLabel.value ?? '');

const durationPresets = [15, 25, 45, 60, 90, 120];

function applyCustomDuration(): void {
    const mins = Number(customMinutesInput.value);
    if (!Number.isFinite(mins) || mins < 1) {
        return;
    }
    setDurationMinutes(mins, sessionLabelInput.value.trim() || null);
}

function selectPreset(mins: number): void {
    customMinutesInput.value = mins;
    setDurationMinutes(mins, sessionLabelInput.value.trim() || null);
}

const circleRadius = 96;
const circleCircumference = 2 * Math.PI * circleRadius;

const strokeDashoffset = computed(() => {
    const progress = progressPercent.value / 100;
    return circleCircumference * (1 - progress);
});

const statusLabel = computed(() => {
    if (phase.value === 'rest') {
        return isRunning.value
            ? 'Resting eyes · Look 20ft away'
            : 'Rest break paused';
    }

    if (isRunning.value) {
        return 'Focus block active';
    }

    return isDefaultState.value ? 'Ready for 20m focus' : 'Focus timer paused';
});

const pillars = [
    {
        step: '20 min',
        title: 'Every 20 minutes of coding',
        description:
            'Prolonged near-point focus locks your eyes’ ciliary muscles in continuous contraction and convergence. Setting a strict 20-minute ceiling prevents cumulative accommodative spasm.',
        icon: Clock,
        tone: 'indigo',
    },
    {
        step: '20 ft',
        title: 'Look at least 20 feet (6 meters) away',
        description:
            'In optometry, 20 feet (~6 meters) approximates optical infinity. Gazing at a distant target across the room or out a window allows your crystalline lens to flatten and relax.',
        icon: Compass,
        tone: 'emerald',
    },
    {
        step: '20 sec',
        title: 'Hold your gaze for 20 full seconds',
        description:
            'It takes roughly 20 seconds for the accommodation system to fully de-tension and for conscious, complete blinking to restore your pre-corneal tear film lipid layer.',
        icon: Timer,
        tone: 'amber',
    },
];

const developerHabits = [
    {
        title: 'Blink deliberately during the 20s break',
        detail: 'Screen concentration drops human blink rates from ~15–20/min down to 4–6/min, causing evaporative dry eye.',
    },
    {
        title: 'Keep your display 20–28 inches (arm’s length) away',
        detail: 'Position the top of your primary monitor 15–20° below horizontal eye level to minimize exposed ocular surface area.',
    },
    {
        title: 'Keep the timer running across sprints',
        detail: 'Clinical testing (Talens-Estarelles et al., 2023) proved that when automated reminders stop, eye strain rebounds within 7 days.',
    },
];
</script>

<template>
    <Head title="20-20-20 Eye Care & Focus Timer" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <PageHeader
            eyebrow="Developer Wellness & Ergonomics"
            title="20-20-20 Eye Care & Stop-Timer"
            description="Protect your vision during deep coding sessions with an evidence-based 20-20-20 timer and reciprocal duration counter backed by peer-reviewed optometric research."
            :icon="Eye"
        >
            <template #actions>
                <Button
                    variant="outline"
                    size="sm"
                    @click="soundEnabled = !soundEnabled"
                >
                    <Volume2 v-if="soundEnabled" class="size-4" />
                    <VolumeX v-else class="size-4" />
                    {{ soundEnabled ? 'Chime On' : 'Muted' }}
                </Button>
                <Button variant="outline" size="sm" @click="triggerRestNow">
                    <Sparkles class="size-4" />
                    Take 20s Break Now
                </Button>
                <Button
                    v-if="!isDefaultState"
                    variant="destructive"
                    size="sm"
                    @click="stopTimer"
                >
                    <Square class="size-3.5 fill-current" />
                    Stop Timer
                </Button>
            </template>
        </PageHeader>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <StatCard
                label="Cycles today"
                :value="cyclesToday"
                hint="20-20-20 breaks completed"
                :icon="CheckCircle2"
                tone="emerald"
            />
            <StatCard
                label="Healthy focus today"
                :value="`${focusMinutesToday}m`"
                hint="Protected screen time"
                :icon="Flame"
                tone="indigo"
            />
            <StatCard
                label="All-time breaks"
                :value="totalCycles"
                hint="Cumulative eye resets"
                :icon="Activity"
                tone="amber"
            />
            <StatCard
                label="Target distance"
                value="20 ft / 6 m"
                hint="Optical infinity relaxation"
                :icon="Eye"
                tone="rose"
            />
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <section
                class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 p-6 text-white shadow-lg md:p-8"
            >
                <div
                    class="pointer-events-none absolute inset-0 opacity-[0.06]"
                    style="
                        background-image:
                            linear-gradient(
                                to right,
                                white 1px,
                                transparent 1px
                            ),
                            linear-gradient(
                                to bottom,
                                white 1px,
                                transparent 1px
                            );
                        background-size: 32px 32px;
                    "
                />
                <div
                    :class="[
                        'pointer-events-none absolute -top-24 -right-16 size-72 rounded-full blur-3xl transition-colors duration-700',
                        phase === 'rest'
                            ? 'bg-emerald-500/25'
                            : 'bg-indigo-500/20',
                    ]"
                />

                <div
                    class="relative flex flex-col items-center justify-between gap-8 lg:flex-row"
                >
                    <div class="relative flex items-center justify-center">
                        <svg
                            class="size-56 -rotate-90 transform"
                            viewBox="0 0 220 220"
                        >
                            <circle
                                cx="110"
                                cy="110"
                                :r="circleRadius"
                                fill="transparent"
                                stroke="currentColor"
                                stroke-width="10"
                                class="text-white/10"
                            />
                            <circle
                                cx="110"
                                cy="110"
                                :r="circleRadius"
                                fill="transparent"
                                stroke="currentColor"
                                stroke-width="10"
                                stroke-linecap="round"
                                :stroke-dasharray="circleCircumference"
                                :stroke-dashoffset="strokeDashoffset"
                                :class="[
                                    'transition-all duration-500',
                                    phase === 'rest'
                                        ? 'text-emerald-400'
                                        : 'text-indigo-400',
                                ]"
                            />
                        </svg>

                        <div
                            class="absolute inset-0 flex flex-col items-center justify-center text-center"
                        >
                            <span
                                :class="[
                                    'rounded-full px-2.5 py-0.5 text-[10px] font-semibold tracking-wider uppercase',
                                    phase === 'rest'
                                        ? 'bg-emerald-500/20 text-emerald-300'
                                        : 'bg-indigo-500/20 text-indigo-200',
                                ]"
                            >
                                {{
                                    phase === 'rest'
                                        ? '20s Eye Break'
                                        : '20m Focus'
                                }}
                            </span>
                            <p
                                class="mt-2 font-mono text-5xl font-bold tracking-tight tabular-nums"
                            >
                                {{ formattedTime }}
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                {{ progressPercent }}% elapsed
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col justify-center">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge
                                variant="outline"
                                :class="[
                                    'border-white/15 bg-white/5 text-xs',
                                    phase === 'rest'
                                        ? 'text-emerald-300'
                                        : 'text-indigo-200',
                                ]"
                            >
                                {{ statusLabel }}
                            </Badge>
                            <span class="text-xs text-slate-400">
                                · Continues running as you navigate the portal
                            </span>
                        </div>

                        <h2
                            class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl"
                        >
                            <template v-if="phase === 'rest'">
                                Look at an object 20 feet (6 meters) away for
                                {{ remainingSeconds }} seconds.
                            </template>
                            <template v-else>
                                Code with focus. Pause every 20 minutes for your
                                eyes.
                            </template>
                        </h2>

                        <p
                            class="mt-2 max-w-xl text-sm leading-relaxed text-slate-300"
                        >
                            <template v-if="phase === 'rest'">
                                Step back from the monitor, soften your focus on
                                a distant point, and blink slowly several times
                                to replenish your tear film.
                            </template>
                            <template v-else>
                                Start the timer while you work. After 20 minutes
                                of screen time, MyDevPortal chimes gently and
                                runs a 20-second distance gaze break.
                            </template>
                        </p>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <Button
                                size="lg"
                                :class="[
                                    'font-semibold',
                                    phase === 'rest'
                                        ? 'bg-emerald-400 text-slate-950 hover:bg-emerald-300'
                                        : 'bg-white text-slate-900 hover:bg-slate-200',
                                ]"
                                @click="toggleTimer"
                            >
                                <Pause v-if="isRunning" class="size-4" />
                                <Play v-else class="size-4" />
                                {{
                                    isRunning
                                        ? 'Pause Timer'
                                        : isDefaultState
                                          ? 'Start 20-20-20 Timer'
                                          : 'Resume Timer'
                                }}
                            </Button>

                            <Button
                                type="button"
                                size="lg"
                                variant="outline"
                                :disabled="isDefaultState"
                                class="border-white/20 bg-white/5 text-white hover:bg-rose-500/20 hover:text-rose-200 disabled:opacity-40"
                                @click="stopTimer"
                            >
                                <RotateCcw class="size-4" />
                                Stop &amp; Reset (20:00)
                            </Button>

                            <Button
                                v-if="phase === 'focus'"
                                type="button"
                                size="lg"
                                variant="outline"
                                class="border-emerald-400/30 bg-emerald-500/10 text-emerald-200 hover:bg-emerald-500/20"
                                @click="triggerRestNow"
                            >
                                <Eye class="size-4" />
                                20s Break Now
                            </Button>

                            <Button
                                v-else
                                type="button"
                                size="lg"
                                variant="outline"
                                class="border-white/20 bg-white/5 text-white hover:bg-white/10"
                                @click="skipRest"
                            >
                                <SkipForward class="size-4" />
                                Skip Break
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="flex flex-col justify-between rounded-2xl border bg-card p-6 shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <Bell class="size-4 text-indigo-500" />
                            <h2 class="text-base font-semibold">
                                Timer Preferences &amp; Ergonomics
                            </h2>
                        </div>
                        <Button
                            v-if="cyclesToday > 0 || focusMinutesToday > 0"
                            variant="ghost"
                            size="sm"
                            class="h-7 text-xs text-muted-foreground"
                            @click="resetDailyStats"
                        >
                            <RefreshCw class="size-3" />
                            Reset today
                        </Button>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Saved automatically in your browser so your routine
                        persists across sessions.
                    </p>

                    <div class="mt-5 space-y-3.5">
                        <label
                            for="pref-auto-cycle"
                            class="flex cursor-pointer items-start gap-3 rounded-xl border bg-muted/20 p-3.5 transition hover:bg-muted/40"
                        >
                            <Checkbox
                                id="pref-auto-cycle"
                                v-model="autoCycle"
                                class="mt-0.5"
                            />
                            <div class="text-xs">
                                <p class="font-semibold text-foreground">
                                    Auto-loop 20-minute cycles
                                </p>
                                <p class="mt-0.5 text-muted-foreground">
                                    Automatically begin the next 20-minute focus
                                    countdown right after your 20-second eye
                                    break finishes.
                                </p>
                            </div>
                        </label>

                        <label
                            for="pref-rest-overlay"
                            class="flex cursor-pointer items-start gap-3 rounded-xl border bg-muted/20 p-3.5 transition hover:bg-muted/40"
                        >
                            <Checkbox
                                id="pref-rest-overlay"
                                v-model="restOverlayEnabled"
                                class="mt-0.5"
                            />
                            <div class="text-xs">
                                <p class="font-semibold text-foreground">
                                    Show 20-second break prompt modal
                                </p>
                                <p class="mt-0.5 text-muted-foreground">
                                    Display a guided 20-second overlay anywhere
                                    in the workspace when it is time to look 20
                                    feet away.
                                </p>
                            </div>
                        </label>

                        <label
                            for="pref-sound"
                            class="flex cursor-pointer items-start gap-3 rounded-xl border bg-muted/20 p-3.5 transition hover:bg-muted/40"
                        >
                            <Checkbox
                                id="pref-sound"
                                v-model="soundEnabled"
                                class="mt-0.5"
                            />
                            <div class="text-xs">
                                <p class="font-semibold text-foreground">
                                    Gentle Web Audio harmonic chime
                                </p>
                                <p class="mt-0.5 text-muted-foreground">
                                    Play a soft audio cue when a 20-second break
                                    starts and ends so you don’t have to watch
                                    the clock while looking away.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <div
                    class="mt-5 rounded-xl border border-amber-500/25 bg-amber-500/5 p-3.5"
                >
                    <div class="flex items-start gap-2.5">
                        <ShieldAlert
                            class="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        <div class="text-xs">
                            <p
                                class="font-semibold text-amber-800 dark:text-amber-300"
                            >
                                Why the Stop / Reset control matters
                            </p>
                            <p
                                class="mt-0.5 leading-relaxed text-muted-foreground"
                            >
                                Stepping away for a meeting, standup, or lunch?
                                Hit
                                <span class="font-medium text-foreground"
                                    >Stop &amp; Reset</span
                                >
                                so your next 20-minute interval starts fresh the
                                moment you sit back down at your keyboard.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Reciprocal Custom Duration Timer Section -->
        <section class="rounded-2xl border bg-card p-6 shadow-sm">
            <div
                class="flex flex-col justify-between gap-4 border-b pb-5 lg:flex-row lg:items-center"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <Hourglass class="size-4 text-sky-500" />
                        <p
                            class="text-[11px] font-semibold tracking-[0.16em] text-sky-600 uppercase dark:text-sky-400"
                        >
                            Reciprocal Work Duration Counter
                        </p>
                    </div>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight">
                        Custom Duration Timer (Auto-Pauses During 20-20-20 Eye
                        Breaks)
                    </h2>
                    <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
                        Track deep work blocks of any duration (e.g. 25m, 45m,
                        60m, 90m). Whenever the 20-20-20 rule enters its
                        20-second eye rest phase, this duration counter
                        <span class="font-medium text-foreground"
                            >automatically pauses</span
                        >
                        and resumes the moment your break completes.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        v-if="isDurationPausedByEyeBreak"
                        variant="outline"
                        class="animate-pulse border-amber-500/40 bg-amber-500/15 px-3 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300"
                    >
                        Auto-Paused by 20-20-20 Eye Break ({{
                            remainingSeconds
                        }}s left)
                    </Badge>
                    <Badge
                        v-else-if="durationRunning"
                        variant="outline"
                        class="border-sky-500/30 bg-sky-500/10 px-3 py-1 text-xs font-semibold text-sky-700 dark:text-sky-300"
                    >
                        Counting Active Work Duration
                    </Badge>
                    <Badge v-else variant="outline" class="px-3 py-1 text-xs">
                        {{
                            isDurationDefaultState ? 'Ready' : 'Duration Paused'
                        }}
                    </Badge>
                </div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_1.15fr]">
                <div
                    :class="[
                        'flex flex-col justify-between rounded-2xl border p-5 transition-colors',
                        isDurationPausedByEyeBreak
                            ? 'border-amber-500/40 bg-amber-500/5'
                            : 'bg-muted/20',
                    ]"
                >
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                {{
                                    durationLabel ||
                                    `${durationTargetMinutes}-Minute Focus Session`
                                }}
                            </span>
                            <span
                                class="font-mono text-xs text-muted-foreground"
                            >
                                Elapsed work: {{ formattedDurationElapsed }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-baseline gap-3">
                            <p
                                class="font-mono text-5xl font-bold tracking-tight tabular-nums"
                            >
                                {{ formattedDurationRemaining }}
                            </p>
                            <span class="text-xs text-muted-foreground">
                                remaining ({{ durationProgressPercent }}%)
                            </span>
                        </div>

                        <ProgressBar
                            class="mt-4"
                            :value="durationProgressPercent"
                            :color="
                                isDurationPausedByEyeBreak ? 'amber' : 'sky'
                            "
                        />

                        <p
                            v-if="isDurationPausedByEyeBreak"
                            class="mt-3 text-xs font-medium text-amber-700 dark:text-amber-300"
                        >
                            20-20-20 Rule is active! Your duration counter is
                            holding at {{ formattedDurationRemaining }} while
                            you look 20 feet away.
                        </p>
                        <p v-else class="mt-3 text-xs text-muted-foreground">
                            Every 20 minutes of work triggers a 20-second eye
                            break without deducting from your work duration.
                        </p>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center gap-2.5">
                        <Button
                            size="default"
                            @click="
                                () => {
                                    if (sessionLabelInput.trim()) {
                                        durationLabel =
                                            sessionLabelInput.trim();
                                    }
                                    toggleDurationTimer();
                                }
                            "
                        >
                            <Pause
                                v-if="
                                    durationRunning &&
                                    !isDurationPausedByEyeBreak
                                "
                                class="size-4"
                            />
                            <Play v-else class="size-4" />
                            {{
                                isDurationPausedByEyeBreak
                                    ? 'Waiting for 20s Break...'
                                    : durationRunning
                                      ? 'Pause Duration'
                                      : isDurationDefaultState
                                        ? 'Start Duration Timer'
                                        : 'Resume Duration'
                            }}
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            :disabled="isDurationDefaultState"
                            @click="stopDurationTimer"
                        >
                            <RotateCcw class="size-4" />
                            Stop &amp; Reset Duration
                        </Button>
                    </div>
                </div>

                <div class="flex flex-col justify-between gap-4">
                    <div>
                        <Label
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Quick Duration Presets
                        </Label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <Button
                                v-for="preset in durationPresets"
                                :key="preset"
                                type="button"
                                size="sm"
                                :variant="
                                    durationTargetMinutes === preset
                                        ? 'default'
                                        : 'outline'
                                "
                                @click="selectPreset(preset)"
                            >
                                {{ preset }} min
                            </Button>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label for="custom-duration-mins" class="text-xs">
                                Custom duration (minutes)
                            </Label>
                            <div class="flex items-center gap-2">
                                <Input
                                    id="custom-duration-mins"
                                    v-model.number="customMinutesInput"
                                    type="number"
                                    min="1"
                                    max="480"
                                    class="h-9"
                                    @change="applyCustomDuration"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="h-9"
                                    @click="applyCustomDuration"
                                >
                                    Set
                                </Button>
                            </div>
                        </div>

                        <div class="grid gap-1.5">
                            <Label for="duration-session-label" class="text-xs">
                                Session / task label (optional)
                            </Label>
                            <Input
                                id="duration-session-label"
                                v-model="sessionLabelInput"
                                placeholder="e.g. Deep work: API refactor"
                                class="h-9"
                                @blur="
                                    durationLabel =
                                        sessionLabelInput.trim() || null
                                "
                            />
                        </div>
                    </div>

                    <label
                        for="pref-sync-duration"
                        class="flex cursor-pointer items-start gap-3 rounded-xl border bg-muted/20 p-3.5 transition hover:bg-muted/40"
                    >
                        <Checkbox
                            id="pref-sync-duration"
                            v-model="syncDurationWithEyeCare"
                            class="mt-0.5"
                        />
                        <div class="text-xs">
                            <p class="font-semibold text-foreground">
                                Auto-start 20-20-20 Eye Care rule when Duration
                                Timer starts
                            </p>
                            <p class="mt-0.5 text-muted-foreground">
                                Ensures the 20-20-20 timer runs alongside your
                                duration timer and automatically pauses the
                                duration count during every 20-second eye rest
                                break.
                            </p>
                        </div>
                    </label>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article
                v-for="pillar in pillars"
                :key="pillar.step"
                class="rounded-2xl border bg-card p-5 shadow-sm"
            >
                <div class="flex items-center justify-between gap-3">
                    <span
                        class="rounded-lg border bg-muted/50 px-2.5 py-1 font-mono text-xs font-bold text-foreground"
                    >
                        {{ pillar.step }}
                    </span>
                    <component
                        :is="pillar.icon"
                        class="size-4 text-muted-foreground"
                    />
                </div>
                <h3 class="mt-3 text-base font-semibold">
                    {{ pillar.title }}
                </h3>
                <p class="mt-1.5 text-xs leading-relaxed text-muted-foreground">
                    {{ pillar.description }}
                </p>
            </article>
        </section>

        <section class="rounded-2xl border bg-card p-6 shadow-sm">
            <div
                class="flex flex-col justify-between gap-4 border-b pb-5 lg:flex-row lg:items-end"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <BookOpenCheck class="size-4 text-emerald-500" />
                        <p
                            class="text-[11px] font-semibold tracking-[0.16em] text-emerald-600 uppercase dark:text-emerald-400"
                        >
                            Peer-Reviewed Literature &amp; Clinical Guidelines
                        </p>
                    </div>
                    <h2 class="mt-1 text-xl font-semibold tracking-tight">
                        Scientific Sources Backing the 20-20-20 Rule
                    </h2>
                    <p class="mt-1 max-w-3xl text-sm text-muted-foreground">
                        Computer Vision Syndrome (Digital Eye Strain) affects
                        over 50% of software developers. Here is the clinical
                        and peer-reviewed research validating why structured
                        20-20-20 breaks work.
                    </p>
                </div>
                <Badge
                    variant="outline"
                    class="w-fit border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                >
                    4 Verified Clinical Citations
                </Badge>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                <article
                    v-for="source in sources"
                    :key="source.id"
                    class="flex flex-col justify-between rounded-xl border bg-muted/15 p-5 transition hover:border-foreground/20 hover:bg-muted/30"
                >
                    <div>
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <Badge variant="outline" class="text-[10px]">
                                {{ source.badge }} · {{ source.year }}
                            </Badge>
                            <span
                                v-if="source.pmid"
                                class="font-mono text-[11px] text-muted-foreground"
                            >
                                PMID: {{ source.pmid }}
                            </span>
                        </div>

                        <h3 class="mt-3 text-sm leading-snug font-semibold">
                            {{ source.title }}
                        </h3>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ source.authors }} —
                            <span class="italic">{{ source.publication }}</span>
                        </p>

                        <p
                            class="mt-3 text-xs leading-relaxed text-muted-foreground"
                        >
                            {{ source.summary }}
                        </p>

                        <div
                            class="mt-3 rounded-lg border bg-background/70 p-3 text-xs leading-relaxed"
                        >
                            <span class="font-semibold text-foreground"
                                >Key finding:
                            </span>
                            <span class="text-muted-foreground">{{
                                source.keyFinding
                            }}</span>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3 pt-2">
                        <a
                            :href="source.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 transition hover:underline dark:text-indigo-400"
                        >
                            Read primary source
                            <ArrowUpRight class="size-3.5" />
                        </a>
                        <a
                            v-if="source.doi"
                            :href="source.doi"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 text-xs text-muted-foreground transition hover:text-foreground hover:underline"
                        >
                            DOI link
                            <ArrowUpRight class="size-3" />
                        </a>
                    </div>
                </article>
            </div>

            <div class="mt-6 rounded-xl border bg-muted/20 p-4">
                <p
                    class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Developer Visual Hygiene Checklist
                </p>
                <div class="mt-3 grid gap-3 md:grid-cols-3">
                    <div
                        v-for="habit in developerHabits"
                        :key="habit.title"
                        class="rounded-lg border bg-background p-3"
                    >
                        <p class="text-xs font-semibold">{{ habit.title }}</p>
                        <p
                            class="mt-1 text-[11px] leading-relaxed text-muted-foreground"
                        >
                            {{ habit.detail }}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
