<script setup lang="ts">
import type { ChatWorkspace } from '@/types';

defineProps<{
    workspaces: ChatWorkspace[];
    activeWorkspaceId: number | null;
    activeChannelId: number | null;
    onlineCount: number;
    isAdmin: boolean;
}>();

const emit = defineEmits<{
    (e: 'select-workspace', id: number): void;
    (e: 'select-channel', id: number): void;
    (e: 'close'): void;
}>();
</script>

<template>
    <!-- Full-view channel/workspace switcher. Mobile only. -->
    <div class="fixed inset-0 z-50 flex flex-col bg-background md:hidden">
        <header class="flex h-16 shrink-0 items-center justify-between border-b border-border px-5">
            <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                Workspaces
            </span>
            <button
                type="button"
                class="-mr-2 flex size-11 items-center justify-center rounded-lg text-foreground"
                aria-label="Close menu"
                @click="emit('close')"
            >
                <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </header>

        <!-- Workspace pills -->
        <div class="flex gap-2 overflow-x-auto border-b border-border px-4 py-3">
            <button
                v-for="w in workspaces"
                :key="w.id"
                type="button"
                class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-colors"
                :class="
                    w.id === activeWorkspaceId
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-muted text-muted-foreground'
                "
                @click="emit('select-workspace', w.id)"
            >
                {{ w.name }}
            </button>
        </div>

        <!-- Channels -->
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                Channels
            </p>
            <ul class="space-y-1">
                <li
                    v-for="channel in workspaces.find((w) => w.id === activeWorkspaceId)?.channels ?? []"
                    :key="channel.id"
                >
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-xl px-4 py-3.5 text-left text-base transition-colors"
                        :class="
                            channel.id === activeChannelId
                                ? 'bg-primary text-primary-foreground'
                                : 'text-foreground hover:bg-muted'
                        "
                        @click="emit('select-channel', channel.id)"
                    >
                        <span
                            class="text-muted-foreground"
                            :class="channel.id === activeChannelId ? 'text-primary-foreground/70' : ''"
                        >#</span>
                        <span class="truncate">{{ channel.name }}</span>
                    </button>
                </li>
            </ul>
        </nav>

        <footer class="flex items-center justify-between border-t border-border px-5 py-3 text-sm text-muted-foreground">
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-green-500"></span>
                {{ onlineCount }} online
            </span>
            <a v-if="isAdmin" href="/admin" class="font-medium text-foreground">Admin</a>
        </footer>
    </div>
</template>
