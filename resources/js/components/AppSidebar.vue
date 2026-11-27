<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    Briefcase,
    CalendarDays,
    Eye,
    FileCode,
    FolderGit2,
    FolderKanban,
    GraduationCap,
    Layers,
    LayoutDashboard,
    ListChecks,
    Lock,
    NotebookPen,
    ShieldCheck,
    Sparkles,
    Target,
    TrendingUp,
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
    useSidebar,
} from '@/components/ui/sidebar';
import type { NavGroup, NavItem } from '@/types';

const { isMobile, setOpenMobile } = useSidebar();

const primaryNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutDashboard,
    },
];

const navGroups: NavGroup[] = [
    {
        title: 'Build & Ship',
        icon: FolderKanban,
        items: [
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
                title: 'Stack',
                href: '/stack',
                icon: Layers,
            },
            {
                title: 'README',
                href: '/readme',
                icon: FileCode,
            },
        ],
    },
    {
        title: 'Growth & CRM',
        icon: Briefcase,
        items: [
            {
                title: 'Pipeline',
                href: '/pipeline',
                icon: TrendingUp,
            },
            {
                title: 'Career',
                href: '/career',
                icon: Target,
            },
            {
                title: 'Learning',
                href: '/learning',
                icon: GraduationCap,
            },
            {
                title: 'Statistics',
                href: '/statistics',
                icon: BarChart3,
            },
        ],
    },
    {
        title: 'Workspace & Care',
        icon: Sparkles,
        items: [
            {
                title: 'Notes',
                href: '/notes',
                icon: NotebookPen,
            },
            {
                title: 'Eye Care',
                href: '/eye-care',
                icon: Eye,
            },
        ],
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

function closeMobileSidebar(): void {
    if (isMobile.value) {
        setOpenMobile(false);
    }
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard" @click="closeMobileSidebar">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                :items="primaryNavItems"
                :groups="navGroups"
                label="Workspace"
            />
        </SidebarContent>

        <SidebarFooter>
            <div
                class="mx-2 mb-1 hidden rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-3 group-data-[collapsible=icon]:hidden md:block"
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
