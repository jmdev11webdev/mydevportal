<script setup lang="ts">
import { reactive, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavGroup, NavItem } from '@/types';

const props = withDefaults(
    defineProps<{
        items?: NavItem[];
        groups?: NavGroup[];
        label?: string;
    }>(),
    {
        items: () => [],
        groups: () => [],
        label: 'Platform',
    },
);

const { isCurrentUrl, currentUrl } = useCurrentUrl();
const { isMobile, setOpenMobile, state, setOpen } = useSidebar();

const openGroups = reactive<Record<string, boolean>>({});

function hasActiveChild(group: NavGroup): boolean {
    return group.items.some((item) => isCurrentUrl(item.href));
}

watch(
    [() => props.groups, currentUrl],
    () => {
        for (const group of props.groups) {
            if (
                openGroups[group.title] === undefined ||
                hasActiveChild(group)
            ) {
                openGroups[group.title] = hasActiveChild(group);
            }
        }
    },
    { immediate: true },
);

function handleGroupToggle(group: NavGroup, nextOpen: boolean): void {
    if (state.value === 'collapsed' && !isMobile.value) {
        setOpen(true);
        openGroups[group.title] = true;
        return;
    }

    openGroups[group.title] = nextOpen;
}

function handleLinkClick(): void {
    if (isMobile.value) {
        setOpenMobile(false);
    }
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href" @click="handleLinkClick">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>

            <Collapsible
                v-for="group in groups"
                :key="group.title"
                as-child
                :open="Boolean(openGroups[group.title])"
                class="group/collapsible"
                @update:open="(val) => handleGroupToggle(group, val)"
            >
                <SidebarMenuItem>
                    <CollapsibleTrigger as-child>
                        <SidebarMenuButton
                            :tooltip="group.title"
                            :is-active="
                                hasActiveChild(group) &&
                                (!openGroups[group.title] ||
                                    (state === 'collapsed' && !isMobile))
                            "
                        >
                            <component :is="group.icon" />
                            <span>{{ group.title }}</span>
                            <ChevronRight
                                class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                            />
                        </SidebarMenuButton>
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <SidebarMenuSub>
                            <SidebarMenuSubItem
                                v-for="subItem in group.items"
                                :key="subItem.title"
                            >
                                <SidebarMenuSubButton
                                    as-child
                                    :is-active="isCurrentUrl(subItem.href)"
                                >
                                    <Link
                                        :href="subItem.href"
                                        @click="handleLinkClick"
                                    >
                                        <component
                                            :is="subItem.icon"
                                            v-if="subItem.icon"
                                        />
                                        <span>{{ subItem.title }}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        </SidebarMenuSub>
                    </CollapsibleContent>
                </SidebarMenuItem>
            </Collapsible>
        </SidebarMenu>
    </SidebarGroup>
</template>
