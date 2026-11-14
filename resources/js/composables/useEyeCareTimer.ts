import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

export type TimerPhase = 'focus' | 'rest';

export type EyeCareSource = {
    id: string;
    title: string;
    authors: string;
    publication: string;
    year: string;
    summary: string;
    keyFinding: string;
    url: string;
    doi?: string;
    pmid?: string;
    badge: string;
};

export const EYE_CARE_SOURCES: EyeCareSource[] = [
    {
        id: 'talens-estarelles-2023',
        title: 'The effects of breaks on digital eye strain, dry eye and binocular vision: Testing the 20-20-20 rule',
        authors:
            'C. Talens-Estarelles, A. Cerviño, S. García-Lázaro, A. Fogelton, A. Sheppard & J. S. Wolffsohn',
        publication: 'Contact Lens and Anterior Eye, Vol. 46, Issue 2, 101744',
        year: '2023',
        badge: 'Peer-Reviewed Clinical Trial',
        summary:
            'Prospective clinical study testing the 20-20-20 rule in symptomatic computer users using automated break reminders.',
        keyFinding:
            'Following 20-20-20 reminders significantly decreased digital eye strain (CVS-Q) and dry eye symptoms (OSDI) and improved accommodative facility. Notably, symptoms rebounded within 1 week after stopping reminders—showing that an active timer is essential to sustain the habit.',
        url: 'https://pubmed.ncbi.nlm.nih.gov/35963776/',
        doi: 'https://doi.org/10.1016/j.clae.2022.101744',
        pmid: '35963776',
    },
    {
        id: 'sheppard-wolffsohn-2018',
        title: 'Digital eye strain: prevalence, measurement and amelioration',
        authors: 'Amy L. Sheppard & James S. Wolffsohn',
        publication: 'BMJ Open Ophthalmology, 3(1), e000146',
        year: '2018',
        badge: 'BMJ Systematic Review',
        summary:
            'Comprehensive clinical review of Digital Eye Strain (DES), affecting over 50% of professional computer users.',
        keyFinding:
            'Sustained near-screen viewing locks the ciliary muscle in constant accommodation and vergence stress while reducing blink rates from ~15–20 blinks/min down to 4–6 blinks/min. Distance breaks restore complete blinking and tear film stability.',
        url: 'https://pmc.ncbi.nlm.nih.gov/articles/PMC6020759/',
        doi: 'https://doi.org/10.1136/bmjophth-2018-000146',
        pmid: '29963645',
    },
    {
        id: 'aoa-guideline',
        title: 'Computer Vision Syndrome (Digital Eye Strain) Clinical Care Guidelines',
        authors: 'American Optometric Association (AOA) & Dr. Jeffrey Anshel',
        publication: 'American Optometric Association Clinical Reference',
        year: 'Guideline',
        badge: 'Optometric Standard',
        summary:
            'Official occupational vision guideline establishing the 20-20-20 rule: every 20 minutes, look at least 20 feet away for 20 seconds.',
        keyFinding:
            'Viewing distances of 20 feet (~6 meters) approximate optical infinity, allowing the eye’s crystalline lens and ciliary muscles to relax from near-point coding fatigue.',
        url: 'https://www.aoa.org/healthy-eyes/eye-and-vision-conditions/computer-vision-syndrome',
    },
    {
        id: 'aao-ergonomics',
        title: 'Computers, Digital Devices and Eye Strain Prevention',
        authors: 'American Academy of Ophthalmology (AAO)',
        publication: 'Ophthalmology Patient & Occupational Health Guidance',
        year: 'Guideline',
        badge: 'Medical Consensus',
        summary:
            'Evidence-based ergonomics and visual hygiene practices for software engineers and heavy screen users.',
        keyFinding:
            'Combines the 20-20-20 rule with keeping monitors an arm’s length away (20–28 inches / 50–70 cm), positioning the top of the screen slightly below eye level (15–20°), and matching display brightness to ambient room lighting.',
        url: 'https://www.aao.org/eye-health/tips-prevention/computer-usage',
    },
];

export const FOCUS_DURATION_SECONDS = 20 * 60; // 20 minutes
export const REST_DURATION_SECONDS = 20; // 20 seconds
export const TARGET_DISTANCE_FEET = 20; // 20 feet (~6 meters)

type PersistedEyeCareState = {
    date: string;
    cyclesToday: number;
    totalCycles: number;
    focusSecondsToday: number;
    soundEnabled: boolean;
    autoCycle: boolean;
    restOverlayEnabled: boolean;
    durationTargetMinutes: number;
    syncDurationWithEyeCare: boolean;
};

const STORAGE_KEY = 'mydevportal.eye-care-20-20-20';

function todayKey(): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

const phase = ref<TimerPhase>('focus');
const isRunning = ref(false);
const remainingSeconds = ref(FOCUS_DURATION_SECONDS);
const cyclesToday = ref(0);
const totalCycles = ref(0);
const focusSecondsToday = ref(0);
const soundEnabled = ref(true);
const autoCycle = ref(true);
const restOverlayEnabled = ref(true);
const restModalDismissed = ref(false);

// Custom Duration Timer state (reciprocates with the 20-20-20 rule)
const durationTargetMinutes = ref(45);
const durationRemainingSeconds = ref(45 * 60);
const durationElapsedSeconds = ref(0);
const durationRunning = ref(false);
const syncDurationWithEyeCare = ref(true);
const durationLabel = ref<string | null>(null);

let initialized = false;
let intervalId: number | null = null;

function loadState(): void {
    if (initialized || typeof window === 'undefined') {
        return;
    }

    initialized = true;

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return;
        }

        const parsed = JSON.parse(raw) as Partial<PersistedEyeCareState>;
        const currentDay = todayKey();

        totalCycles.value =
            typeof parsed.totalCycles === 'number' ? parsed.totalCycles : 0;
        soundEnabled.value =
            typeof parsed.soundEnabled === 'boolean'
                ? parsed.soundEnabled
                : true;
        autoCycle.value =
            typeof parsed.autoCycle === 'boolean' ? parsed.autoCycle : true;
        restOverlayEnabled.value =
            typeof parsed.restOverlayEnabled === 'boolean'
                ? parsed.restOverlayEnabled
                : true;
        syncDurationWithEyeCare.value =
            typeof parsed.syncDurationWithEyeCare === 'boolean'
                ? parsed.syncDurationWithEyeCare
                : true;

        if (
            typeof parsed.durationTargetMinutes === 'number' &&
            parsed.durationTargetMinutes >= 1 &&
            parsed.durationTargetMinutes <= 480
        ) {
            durationTargetMinutes.value = parsed.durationTargetMinutes;
            durationRemainingSeconds.value = parsed.durationTargetMinutes * 60;
        }

        if (parsed.date === currentDay) {
            cyclesToday.value =
                typeof parsed.cyclesToday === 'number' ? parsed.cyclesToday : 0;
            focusSecondsToday.value =
                typeof parsed.focusSecondsToday === 'number'
                    ? parsed.focusSecondsToday
                    : 0;
        } else {
            cyclesToday.value = 0;
            focusSecondsToday.value = 0;
        }
    } catch {
        // Ignore storage errors in restricted environments
    }
}

function saveState(): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        const payload: PersistedEyeCareState = {
            date: todayKey(),
            cyclesToday: cyclesToday.value,
            totalCycles: totalCycles.value,
            focusSecondsToday: focusSecondsToday.value,
            soundEnabled: soundEnabled.value,
            autoCycle: autoCycle.value,
            restOverlayEnabled: restOverlayEnabled.value,
            durationTargetMinutes: durationTargetMinutes.value,
            syncDurationWithEyeCare: syncDurationWithEyeCare.value,
        };
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
    } catch {
        // Ignore storage errors
    }
}

function playChime(type: 'break-start' | 'break-end'): void {
    if (!soundEnabled.value || typeof window === 'undefined') {
        return;
    }

    try {
        const AudioCtx =
            window.AudioContext ||
            (window as unknown as { webkitAudioContext?: typeof AudioContext })
                .webkitAudioContext;

        if (!AudioCtx) {
            return;
        }

        const ctx = new AudioCtx();
        const now = ctx.currentTime;
        const notes =
            type === 'break-start'
                ? [523.25, 659.25, 783.99]
                : [783.99, 1046.5];

        notes.forEach((freq, index) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, now + index * 0.14);

            gain.gain.setValueAtTime(0.0001, now + index * 0.14);
            gain.gain.exponentialRampToValueAtTime(
                0.08,
                now + index * 0.14 + 0.03,
            );
            gain.gain.exponentialRampToValueAtTime(
                0.0001,
                now + index * 0.14 + 0.35,
            );

            osc.connect(gain);
            gain.connect(ctx.destination);

            osc.start(now + index * 0.14);
            osc.stop(now + index * 0.14 + 0.38);
        });
    } catch {
        // AudioContext may be blocked if no user interaction occurred yet
    }
}

function ensureTickerState(): void {
    if (typeof window === 'undefined') {
        return;
    }

    if (isRunning.value || durationRunning.value) {
        if (intervalId === null) {
            intervalId = window.setInterval(tick, 1000);
        }
    } else if (intervalId !== null) {
        window.clearInterval(intervalId);
        intervalId = null;
    }
}

function transitionToRest(): void {
    phase.value = 'rest';
    remainingSeconds.value = REST_DURATION_SECONDS;
    restModalDismissed.value = false;
    playChime('break-start');

    const pauseNote = durationRunning.value
        ? ' Your active Duration Timer has been automatically paused for 20s.'
        : '';

    toast.info('20-20-20 Eye Break: Look 20 feet away for 20 seconds', {
        description: `Relax your ciliary muscles and blink slowly.${pauseNote}`,
        duration: 8000,
    });
}

function completeRestCycle(): void {
    cyclesToday.value += 1;
    totalCycles.value += 1;
    phase.value = 'focus';
    remainingSeconds.value = FOCUS_DURATION_SECONDS;
    restModalDismissed.value = false;
    saveState();
    playChime('break-end');

    const resumeNote = durationRunning.value
        ? ' Your Duration Timer has automatically resumed.'
        : '';

    toast.success('20-20-20 cycle complete! Eyes refreshed.', {
        description:
            (autoCycle.value
                ? 'Starting your next 20-minute focus session.'
                : 'Timer is ready for your next 20-minute block.') + resumeNote,
    });

    if (!autoCycle.value) {
        isRunning.value = false;
        ensureTickerState();
    }
}

function tick(): void {
    const isEyeRestActive = phase.value === 'rest' && isRunning.value;

    // 1. Update Custom Duration Timer ONLY when 20-20-20 eye break is NOT active
    if (durationRunning.value && !isEyeRestActive) {
        durationElapsedSeconds.value += 1;

        if (durationRemainingSeconds.value > 1) {
            durationRemainingSeconds.value -= 1;
        } else {
            durationRemainingSeconds.value = 0;
            durationRunning.value = false;
            playChime('break-end');
            toast.success(
                durationLabel.value
                    ? `Duration complete: ${durationLabel.value}`
                    : `${durationTargetMinutes.value}-minute focus duration completed!`,
                {
                    description: `Total active focus time: ${formatHms(durationElapsedSeconds.value)}.`,
                },
            );
            ensureTickerState();
        }
    }

    // 2. Update 20-20-20 Eye Care Timer
    if (!isRunning.value) {
        return;
    }

    if (phase.value === 'focus') {
        focusSecondsToday.value += 1;
        if (focusSecondsToday.value % 15 === 0) {
            saveState();
        }
    }

    if (remainingSeconds.value > 1) {
        remainingSeconds.value -= 1;
        return;
    }

    if (phase.value === 'focus') {
        transitionToRest();
    } else {
        completeRestCycle();
    }
}

function startTimer(): void {
    loadState();

    if (isRunning.value) {
        return;
    }

    isRunning.value = true;
    ensureTickerState();
}

function pauseTimer(): void {
    isRunning.value = false;
    ensureTickerState();
    saveState();
}

function toggleTimer(): void {
    if (isRunning.value) {
        pauseTimer();
    } else {
        startTimer();
    }
}

/**
 * Stop-timer: halts the 20-20-20 countdown and resets back to 20:00 focus.
 */
function stopTimer(): void {
    isRunning.value = false;
    phase.value = 'focus';
    remainingSeconds.value = FOCUS_DURATION_SECONDS;
    restModalDismissed.value = false;
    ensureTickerState();
    saveState();
}

function triggerRestNow(): void {
    loadState();
    phase.value = 'rest';
    remainingSeconds.value = REST_DURATION_SECONDS;
    restModalDismissed.value = false;
    playChime('break-start');

    if (!isRunning.value) {
        startTimer();
    }
}

function skipRest(): void {
    phase.value = 'focus';
    remainingSeconds.value = FOCUS_DURATION_SECONDS;
    restModalDismissed.value = false;
    saveState();
}

function dismissRestOverlay(): void {
    restModalDismissed.value = true;
}

function resetDailyStats(): void {
    cyclesToday.value = 0;
    focusSecondsToday.value = 0;
    saveState();
    toast.success('Today’s 20-20-20 session stats have been reset.');
}

// --- Custom Duration Timer Controls (Reciprocal with 20-20-20 Rule) ---

function setDurationMinutes(
    minutes: number,
    label: string | null = null,
): void {
    loadState();
    const clamped = Math.max(1, Math.min(480, Math.round(minutes)));
    durationTargetMinutes.value = clamped;
    durationRemainingSeconds.value = clamped * 60;
    durationElapsedSeconds.value = 0;
    if (label !== null) {
        durationLabel.value = label;
    }
    saveState();
}

function startDurationTimer(options?: {
    minutes?: number;
    label?: string | null;
}): void {
    loadState();

    if (options?.minutes) {
        setDurationMinutes(options.minutes, options.label ?? null);
    } else if (options?.label !== undefined) {
        durationLabel.value = options.label;
    }

    if (durationRemainingSeconds.value <= 0) {
        durationRemainingSeconds.value = durationTargetMinutes.value * 60;
        durationElapsedSeconds.value = 0;
    }

    durationRunning.value = true;

    // Reciprocate: when synced, also start the 20-20-20 rule timer so 20m breaks happen automatically
    if (syncDurationWithEyeCare.value && !isRunning.value) {
        isRunning.value = true;
    }

    ensureTickerState();
}

function pauseDurationTimer(): void {
    durationRunning.value = false;
    ensureTickerState();
}

function toggleDurationTimer(): void {
    if (durationRunning.value) {
        pauseDurationTimer();
    } else {
        startDurationTimer();
    }
}

function stopDurationTimer(): void {
    durationRunning.value = false;
    durationRemainingSeconds.value = durationTargetMinutes.value * 60;
    durationElapsedSeconds.value = 0;
    durationLabel.value = null;
    ensureTickerState();
}

function formatHms(totalSeconds: number): string {
    const hrs = Math.floor(totalSeconds / 3600);
    const mins = Math.floor((totalSeconds % 3600) / 60);
    const secs = totalSeconds % 60;

    if (hrs > 0) {
        return `${String(hrs).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }

    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

watch(
    [
        soundEnabled,
        autoCycle,
        restOverlayEnabled,
        durationTargetMinutes,
        syncDurationWithEyeCare,
    ],
    () => {
        if (initialized) {
            saveState();
        }
    },
);

export function useEyeCareTimer() {
    loadState();

    const totalPhaseSeconds = computed(() =>
        phase.value === 'focus'
            ? FOCUS_DURATION_SECONDS
            : REST_DURATION_SECONDS,
    );

    const progressPercent = computed(() => {
        const total = totalPhaseSeconds.value;
        const elapsed = total - remainingSeconds.value;
        return Math.min(100, Math.max(0, Math.round((elapsed / total) * 100)));
    });

    const formattedTime = computed(() => formatHms(remainingSeconds.value));

    const focusMinutesToday = computed(() =>
        Math.floor(focusSecondsToday.value / 60),
    );

    const isDefaultState = computed(
        () =>
            !isRunning.value &&
            phase.value === 'focus' &&
            remainingSeconds.value === FOCUS_DURATION_SECONDS,
    );

    const showRestOverlay = computed(
        () =>
            phase.value === 'rest' &&
            isRunning.value &&
            restOverlayEnabled.value &&
            !restModalDismissed.value,
    );

    // Reciprocal Duration Timer computed properties
    const isDurationPausedByEyeBreak = computed(
        () =>
            durationRunning.value && phase.value === 'rest' && isRunning.value,
    );

    const durationTotalSeconds = computed(
        () => durationTargetMinutes.value * 60,
    );

    const durationProgressPercent = computed(() => {
        const total = durationTotalSeconds.value;
        if (total <= 0) {
            return 0;
        }
        const elapsed = total - durationRemainingSeconds.value;
        return Math.min(100, Math.max(0, Math.round((elapsed / total) * 100)));
    });

    const formattedDurationRemaining = computed(() =>
        formatHms(durationRemainingSeconds.value),
    );

    const formattedDurationElapsed = computed(() =>
        formatHms(durationElapsedSeconds.value),
    );

    const isDurationDefaultState = computed(
        () =>
            !durationRunning.value &&
            durationElapsedSeconds.value === 0 &&
            durationRemainingSeconds.value === durationTotalSeconds.value,
    );

    return {
        phase,
        isRunning,
        remainingSeconds,
        totalPhaseSeconds,
        progressPercent,
        formattedTime,
        cyclesToday,
        totalCycles,
        focusSecondsToday,
        focusMinutesToday,
        soundEnabled,
        autoCycle,
        restOverlayEnabled,
        showRestOverlay,
        isDefaultState,
        sources: EYE_CARE_SOURCES,
        startTimer,
        pauseTimer,
        toggleTimer,
        stopTimer,
        triggerRestNow,
        skipRest,
        dismissRestOverlay,
        resetDailyStats,
        // Reciprocal Custom Duration Timer
        durationTargetMinutes,
        durationRemainingSeconds,
        durationElapsedSeconds,
        durationRunning,
        syncDurationWithEyeCare,
        durationLabel,
        isDurationPausedByEyeBreak,
        durationTotalSeconds,
        durationProgressPercent,
        formattedDurationRemaining,
        formattedDurationElapsed,
        isDurationDefaultState,
        setDurationMinutes,
        startDurationTimer,
        pauseDurationTimer,
        toggleDurationTimer,
        stopDurationTimer,
    };
}
