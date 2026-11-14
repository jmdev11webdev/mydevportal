<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarDays,
    Eye,
    FileCode,
    FolderGit2,
    GraduationCap,
    LayoutDashboard,
    ListChecks,
    Lock,
    NotebookPen,
    ShieldCheck,
    Target,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutDashboard,
    },
    {
        title: 'Projects',
        href: '/projects',
        icon: FolderGit2,
    },
    {
        title: 'Tasks',
        href: '/tasks',
        icon: ListChecks,
    },
    {
        title: 'Calendar',
        href: '/calendar',
        icon: CalendarDays,
    },
    {
        title: 'Learning',
        href: '/learning',
        icon: GraduationCap,
    },
    {
        title: 'Notes',
        href: '/notes',
        icon: NotebookPen,
    },
    {
        title: 'Career',
        href: '/career',
        icon: Target,
    },
    {
        title: 'README',
        href: '/readme',
        icon: FileCode,
    },
    {
        title: 'Eye Care',
        href: '/eye-care',
        icon: Eye,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Privacy & security',
        href: '/settings/security',
        icon: ShieldCheck,
    },
    {
        title: 'Source code',
        href: 'https://www.github.com/jmdev11webdev/mydevportal',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Workspace" />
        </SidebarContent>

        <SidebarFooter>
            <div
                class="mx-2 mb-1 hidden rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3 group-data-[collapsible=icon]:hidden"
            >
                <div class="flex items-center gap-2">
                    <Lock
                        class="size-3.5 text-emerald-600 dark:text-emerald-400"
                    />
                    <p class="text-[11px] font-semibold">Private by default</p>
                </div>
                <p
                    class="mt-1 text-[10px] leading-relaxed text-muted-foreground"
                >
                    Your notes, goals and career data stay in your workspace.
                </p>
            </div>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
