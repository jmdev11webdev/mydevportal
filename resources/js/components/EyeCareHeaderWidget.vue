<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpenCheck,
    Eye,
    Pause,
    Play,
    RotateCcw,
    SkipForward,
    Sparkles,
    Square,
    Timer,
    Volume2,
    VolumeX,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useEyeCareTimer } from '@/composables/useEyeCareTimer';

const {
    phase,
    isRunning,
    remainingSeconds,
    formattedTime,
    cyclesToday,
    soundEnabled,
    showRestOverlay,
    isDefaultState,
    toggleTimer,
    stopTimer,
    skipRest,
    dismissRestOverlay,
    // Reciprocal Duration Timer
    durationRunning,
    durationLabel,
    isDurationPausedByEyeBreak,
    formattedDurationRemaining,
    formattedDurationElapsed,
    isDurationDefaultState,
    toggleDurationTimer,
    stopDurationTimer,
} = useEyeCareTimer();
</script>

<template>
    <div class="flex items-center gap-2">
        <!-- Custom Duration Timer Pill (shown when active or mid-session) -->
        <div
            v-if="!isDurationDefaultState"
            :class="[
                'flex items-center gap-1.5 rounded-xl border px-2.5 py-1 text-xs transition-colors',
                isDurationPausedByEyeBreak
                    ? 'border-amber-500/40 bg-amber-500/10 text-amber-700 dark:text-amber-300'
                    : durationRunning
                      ? 'border-sky-500/30 bg-sky-500/10 text-sky-700 dark:text-sky-300'
                      : 'border-border bg-muted/40 text-muted-foreground',
            ]"
        >
            <TooltipProvider :delay-duration="0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Link
                            href="/eye-care"
                            class="flex items-center gap-1.5 font-medium transition hover:opacity-80"
                        >
                            <Timer class="size-3.5 shrink-0" />
                            <span
                                v-if="isDurationPausedByEyeBreak"
                                class="hidden text-[11px] font-semibold md:inline"
                            >
                                Paused (20-20-20)
                            </span>
                            <span
                                v-else-if="durationLabel"
                                class="hidden max-w-28 truncate text-[11px] lg:inline"
                            >
                                {{ durationLabel }}
                            </span>
                            <span v-else class="hidden text-[11px] sm:inline">
                                Duration
                            </span>
                            <span
                                class="font-mono text-xs font-semibold tabular-nums"
                            >
                                {{ formattedDurationRemaining }}
                            </span>
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent side="bottom" class="max-w-xs">
                        <p class="font-semibold">
                            {{ durationLabel || 'Custom Duration Timer' }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-muted-foreground">
                            Elapsed: {{ formattedDurationElapsed }} ·
                            {{
                                isDurationPausedByEyeBreak
                                    ? 'Automatically paused while 20-20-20 eye break is active.'
                                    : 'Automatically pauses whenever a 20-20-20 eye break starts.'
                            }}
                        </p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <div class="mx-0.5 h-3.5 w-px bg-border" />

            <button
                type="button"
                class="inline-flex size-5 items-center justify-center rounded-md transition hover:bg-background/80 hover:text-foreground"
                :aria-label="
                    durationRunning
                        ? 'Pause duration timer'
                        : 'Resume duration timer'
                "
                @click="toggleDurationTimer"
            >
                <Pause
                    v-if="durationRunning && !isDurationPausedByEyeBreak"
                    class="size-3"
                />
                <Play v-else class="size-3" />
            </button>

            <button
                type="button"
                class="inline-flex size-5 items-center justify-center rounded-md text-rose-500 transition hover:bg-rose-500/10"
                aria-label="Stop duration timer"
                @click="stopDurationTimer"
            >
                <Square class="size-2.5 fill-current" />
            </button>
        </div>

        <!-- 20-20-20 Eye Care Timer Pill -->
        <div
            :class="[
                'flex items-center gap-1.5 rounded-xl border px-2.5 py-1 text-xs transition-colors',
                phase === 'rest'
                    ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                    : isRunning
                      ? 'border-indigo-500/30 bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                      : 'border-border bg-muted/40 text-muted-foreground',
            ]"
        >
            <TooltipProvider :delay-duration="0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Link
                            href="/eye-care"
                            class="flex items-center gap-1.5 font-medium transition hover:opacity-80"
                        >
                            <Eye
                                :class="[
                                    'size-3.5 shrink-0',
                                    phase === 'rest'
                                        ? 'animate-pulse text-emerald-500'
                                        : isRunning
                                          ? 'text-indigo-500'
                                          : 'text-muted-foreground',
                                ]"
                            />
                            <span class="hidden sm:inline">
                                {{
                                    phase === 'rest' ? 'Look 20ft' : '20-20-20'
                                }}
                            </span>
                            <span
                                class="font-mono text-xs font-semibold tabular-nums"
                            >
                                {{ formattedTime }}
                            </span>
                        </Link>
                    </TooltipTrigger>
                    <TooltipContent side="bottom" class="max-w-xs">
                        <p class="font-semibold">
                            20-20-20 Eye Care Timer ({{
                                phase === 'rest'
                                    ? '20s Rest Break'
                                    : '20m Focus Block'
                            }})
                        </p>
                        <p class="mt-0.5 text-[11px] text-muted-foreground">
                            Every 20 minutes, look 20 feet (6m) away for 20
                            seconds. Click to open full timer &amp; clinical
                            studies.
                        </p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <div class="mx-0.5 h-3.5 w-px bg-border" />

            <TooltipProvider :delay-duration="0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex size-5 items-center justify-center rounded-md transition hover:bg-background/80 hover:text-foreground"
                            :aria-label="
                                isRunning
                                    ? 'Pause 20-20-20 timer'
                                    : 'Start 20-20-20 timer'
                            "
                            @click="toggleTimer"
                        >
                            <Pause v-if="isRunning" class="size-3" />
                            <Play v-else class="size-3" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">
                        <p>{{ isRunning ? 'Pause timer' : 'Start timer' }}</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <TooltipProvider v-if="!isDefaultState" :delay-duration="0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex size-5 items-center justify-center rounded-md text-rose-500 transition hover:bg-rose-500/10"
                            aria-label="Stop and reset 20-20-20 timer"
                            @click="stopTimer"
                        >
                            <Square class="size-2.5 fill-current" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">
                        <p>Stop &amp; reset timer</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>

            <Badge
                v-if="cyclesToday > 0"
                variant="outline"
                class="hidden h-4 border-emerald-500/30 bg-emerald-500/10 px-1.5 text-[10px] text-emerald-700 md:inline-flex dark:text-emerald-300"
            >
                {{ cyclesToday }}×
            </Badge>
        </div>
    </div>

    <Dialog
        :open="showRestOverlay"
        @update:open="
            (open) => {
                if (!open) dismissRestOverlay();
            }
        "
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <div class="flex items-center justify-between gap-2">
                    <Badge
                        variant="outline"
                        class="gap-1.5 border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                    >
                        <Sparkles class="size-3" />
                        20-20-20 Visual Break
                    </Badge>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs text-muted-foreground transition hover:bg-muted hover:text-foreground"
                        @click="soundEnabled = !soundEnabled"
                    >
                        <Volume2 v-if="soundEnabled" class="size-3.5" />
                        <VolumeX v-else class="size-3.5" />
                        <span>{{ soundEnabled ? 'Sound on' : 'Muted' }}</span>
                    </button>
                </div>
                <DialogTitle class="mt-2 text-xl">
                    Look 20 feet (6 meters) away
                </DialogTitle>
                <DialogDescription>
                    Give your eyes 20 seconds to relax accommodation and restore
                    your natural blink rate.
                </DialogDescription>
            </DialogHeader>

            <div
                class="my-2 flex flex-col items-center justify-center rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-6 text-center"
            >
                <div
                    class="flex size-24 items-center justify-center rounded-full border-4 border-emerald-500/40 bg-background shadow-inner"
                >
                    <span
                        class="font-mono text-3xl font-bold tracking-tight text-emerald-600 tabular-nums dark:text-emerald-400"
                    >
                        {{ remainingSeconds }}s
                    </span>
                </div>
                <p class="mt-4 text-sm font-medium text-foreground">
                    Focus on a distant object across the room or out a window.
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Blink slowly 3–5 times to recoat your cornea’s tear film
                    (Talens-Estarelles et al., 2023).
                </p>

                <div
                    v-if="isDurationPausedByEyeBreak"
                    class="mt-4 inline-flex items-center gap-1.5 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-[11px] font-medium text-amber-700 dark:text-amber-300"
                >
                    <Timer class="size-3.5" />
                    Duration timer ({{ formattedDurationRemaining }}) is
                    auto-paused during this 20s break
                </div>
            </div>

            <DialogFooter
                class="flex flex-col gap-2 sm:flex-row sm:justify-between"
            >
                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="stopTimer"
                    >
                        <RotateCcw class="size-3.5" />
                        Stop timer
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        as-child
                        @click="dismissRestOverlay"
                    >
                        <Link href="/eye-care">
                            <BookOpenCheck class="size-3.5" />
                            View study
                        </Link>
                    </Button>
                </div>
                <Button type="button" size="sm" @click="skipRest">
                    <SkipForward class="size-3.5" />
                    Skip break
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
