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
            type === 'break-start' ? [523.25, 659.25, 783.99] : [783.99, 1046.5];

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

function clearTicker(): void {
    if (intervalId !== null && typeof window !== 'undefined') {
        window.clearInterval(intervalId);
        intervalId = null;
    }
}

function transitionToRest(): void {
    phase.value = 'rest';
    remainingSeconds.value = REST_DURATION_SECONDS;
    restModalDismissed.value = false;
    playChime('break-start');

    toast.info('20-20-20 Eye Break: Look 20 feet away for 20 seconds', {
        description:
            'Relax your ciliary muscles and blink slowly to refresh your tear film.',
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

    toast.success('20-20-20 cycle complete! Eyes refreshed.', {
        description: autoCycle.value
            ? 'Starting your next 20-minute focus session.'
            : 'Timer is ready whenever you want to start your next 20-minute block.',
    });

    if (!autoCycle.value) {
        pauseTimer();
    }
}

function tick(): void {
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
    clearTicker();

    if (typeof window !== 'undefined') {
        intervalId = window.setInterval(tick, 1000);
    }
}

function pauseTimer(): void {
    isRunning.value = false;
    clearTicker();
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
 * Stop-timer: halts any active countdown and resets back to the start of a 20-minute focus block.
 */
function stopTimer(): void {
    isRunning.value = false;
    clearTicker();
    phase.value = 'focus';
    remainingSeconds.value = FOCUS_DURATION_SECONDS;
    restModalDismissed.value = false;
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

watch([soundEnabled, autoCycle, restOverlayEnabled], () => {
    if (initialized) {
        saveState();
    }
});

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

    const formattedTime = computed(() => {
        const mins = Math.floor(remainingSeconds.value / 60);
        const secs = remainingSeconds.value % 60;
        return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    });

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
    };
}
