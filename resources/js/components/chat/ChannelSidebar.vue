<script setup lang="ts">
import type { ChatWorkspace } from '@/types';

defineProps<{
    workspace: ChatWorkspace | null;
    activeChannelId: number | null;
    onlineCount: number;
}>();

const emit = defineEmits<{
    (e: 'select', channelId: number): void;
}>();
</script>

<template>
    <aside class="h-full w-60 flex-col border-r border-border bg-sidebar">
        <header class="flex h-14 items-center border-b border-border px-4">
            <h1 class="truncate font-semibold tracking-tight">
                {{ workspace?.name ?? 'No workspace' }}
            </h1>
        </header>

        <div class="flex-1 overflow-y-auto px-2 py-3">
            <p class="px-2 pb-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                Channels
            </p>

            <ul class="space-y-0.5">
                <li v-for="channel in workspace?.channels ?? []" :key="channel.id">
                    <button
                        type="button"
                        class="flex w-full items-center gap-1 rounded-md px-2 py-1.5 text-left text-sm transition-colors"
                        :class="
                            channel.id === activeChannelId
                                ? 'bg-primary text-primary-foreground'
                                : 'text-foreground/80 hover:bg-muted'
                        "
                        @click="emit('select', channel.id)"
                    >
                        <span class="text-muted-foreground" :class="channel.id === activeChannelId ? 'text-primary-foreground/70' : ''">#</span>
                        <span class="truncate">{{ channel.name }}</span>
                    </button>
                </li>
            </ul>

            <p
                v-if="!(workspace?.channels?.length)"
                class="px-2 py-2 text-sm text-muted-foreground"
            >
                No channels yet.
            </p>
        </div>

        <footer class="border-t border-border px-4 py-2 text-xs text-muted-foreground">
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2 rounded-full bg-green-500"></span>
                {{ onlineCount }} online
            </span>
        </footer>
    </aside>
</template>
