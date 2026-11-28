<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Lock, ShieldCheck, Sparkles } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="dark relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-[#060913] p-6 text-slate-100 md:p-10"
    >
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
            class="pointer-events-none absolute -top-40 left-1/2 size-[600px] -translate-x-1/2 rounded-full bg-indigo-600/20 blur-3xl"
        />
        <div
            class="pointer-events-none absolute right-0 bottom-0 size-96 rounded-full bg-emerald-500/10 blur-3xl"
        />

        <div class="relative z-10 w-full max-w-md">
            <div class="mb-6 flex flex-col items-center text-center">
                <Link
                    :href="home()"
                    class="inline-flex items-center gap-2.5 font-semibold tracking-tight text-white transition hover:opacity-90"
                >
                    <AppLogoIcon class="size-9 rounded-xl shadow-sm" />
                    <span class="text-base">MyDevPortal</span>
                </Link>

                <div
                    class="mt-4 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium tracking-wide text-slate-300"
                >
                    <Sparkles class="size-3 text-emerald-400" />
                    Privacy-first developer workspace
                </div>
            </div>

            <div
                class="rounded-2xl border border-white/10 bg-slate-950/75 p-6 shadow-2xl shadow-black/50 backdrop-blur-xl sm:p-8"
            >
                <div class="mb-6 space-y-1.5 text-center">
                    <h1 class="text-xl font-semibold tracking-tight text-white">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-slate-400">
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>

            <div
                class="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs text-slate-500"
            >
                <span class="flex items-center gap-1.5">
                    <Lock class="size-3.5 text-emerald-400" />
                    Local SQLite · No trackers
                </span>
                <span class="flex items-center gap-1.5">
                    <ShieldCheck class="size-3.5 text-emerald-400" />
                    Passkeys &amp; 2FA
                </span>
            </div>
        </div>
    </div>
</template>
