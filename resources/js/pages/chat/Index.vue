<script setup lang="ts">
import ChannelSidebar from '@/components/chat/ChannelSidebar.vue';
import ChatMobileNav from '@/components/chat/ChatMobileNav.vue';
import MessagePane from '@/components/chat/MessagePane.vue';
import WorkspaceRail from '@/components/chat/WorkspaceRail.vue';
import { api } from '@/lib/api';
import type { ChatChannel, ChatMessage, ChatWorkspace } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();
const currentUser = computed(() => page.props.auth.user as { id: number; name: string; is_admin?: boolean });
const isAdmin = computed(() => Boolean(currentUser.value?.is_admin));

const workspaces = ref<ChatWorkspace[]>([]);
const activeWorkspaceId = ref<number | null>(null);
const activeChannelId = ref<number | null>(null);
const messages = ref<ChatMessage[]>([]);
const onlineCount = ref(0);
const mobileNavOpen = ref(false);

const activeWorkspace = computed(
    () => workspaces.value.find((w) => w.id === activeWorkspaceId.value) ?? null,
);
const activeChannel = computed<ChatChannel | null>(
    () => activeWorkspace.value?.channels.find((c) => c.id === activeChannelId.value) ?? null,
);

// --- Real-time wiring ---------------------------------------------------

let presenceWorkspaceId: number | null = null;
let subscribedChannelId: number | null = null;

function subscribeToChannel(channelId: number) {
    if (subscribedChannelId === channelId) return;
    if (subscribedChannelId !== null) {
        window.Echo.leave(`channel.${subscribedChannelId}`);
    }
    subscribedChannelId = channelId;

    window.Echo.private(`channel.${channelId}`).listen('.MessageSent', (payload: ChatMessage) => {
        if (payload.channel_id !== activeChannelId.value) return;
        if (messages.value.some((m) => m.id === payload.id)) return;
        messages.value.push(payload);
    });
}

function subscribeToPresence(workspaceId: number) {
    if (presenceWorkspaceId === workspaceId) return;
    if (presenceWorkspaceId !== null) {
        window.Echo.leave(`workspace.${presenceWorkspaceId}`);
    }
    presenceWorkspaceId = workspaceId;

    window.Echo.join(`workspace.${workspaceId}`)
        .here((users: unknown[]) => (onlineCount.value = users.length))
        .joining(() => (onlineCount.value += 1))
        .leaving(() => (onlineCount.value = Math.max(0, onlineCount.value - 1)));
}

// --- Data loading -------------------------------------------------------

async function loadWorkspaces() {
    const data = await api.get<{ workspaces: ChatWorkspace[] }>('/api/workspaces');
    workspaces.value = data.workspaces;
    if (!activeWorkspaceId.value && workspaces.value.length) {
        selectWorkspace(workspaces.value[0].id);
    }
}

async function loadMessages(channelId: number) {
    const data = await api.get<{ messages: ChatMessage[] }>(
        `/api/channels/${channelId}/messages`,
    );
    messages.value = data.messages;
}

function selectWorkspace(id: number) {
    activeWorkspaceId.value = id;
    subscribeToPresence(id);
    const first = activeWorkspace.value?.channels[0];
    activeChannelId.value = first ? first.id : null;
}

function selectChannel(channelId: number) {
    activeChannelId.value = channelId;
    mobileNavOpen.value = false; // drop straight into the conversation on mobile
}

async function sendMessage(body: string) {
    if (!activeChannelId.value) return;
    const channelId = activeChannelId.value;
    const data = await api.post<{ message: ChatMessage }>(
        `/api/channels/${channelId}/messages`,
        { body },
    );
    // We broadcast toOthers(), so append our own message locally.
    if (!messages.value.some((m) => m.id === data.message.id)) {
        messages.value.push(data.message);
    }
}

watch(activeChannelId, (id) => {
    messages.value = [];
    if (id) {
        subscribeToChannel(id);
        loadMessages(id);
    }
});

onMounted(loadWorkspaces);

onBeforeUnmount(() => {
    if (subscribedChannelId !== null) window.Echo.leave(`channel.${subscribedChannelId}`);
    if (presenceWorkspaceId !== null) window.Echo.leave(`workspace.${presenceWorkspaceId}`);
});
</script>

<template>
    <Head title="Chat" />

    <div class="flex h-screen w-full overflow-hidden bg-background text-foreground">
        <!-- Desktop: persistent three-pane. Hidden on mobile. -->
        <WorkspaceRail
            class="hidden md:flex"
            :workspaces="workspaces"
            :active-id="activeWorkspaceId"
            :is-admin="isAdmin"
            @select="selectWorkspace"
        />
        <ChannelSidebar
            class="hidden md:flex"
            :workspace="activeWorkspace"
            :active-channel-id="activeChannelId"
            :online-count="onlineCount"
            @select="selectChannel"
        />

        <!-- Main column -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Mobile header: workspace + channel + burger. Hidden on desktop. -->
            <header class="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-border px-4 md:hidden">
                <div class="min-w-0">
                    <p class="truncate text-xs font-medium text-muted-foreground">
                        {{ activeWorkspace?.name ?? 'Neunsoft Chat' }}
                    </p>
                    <p class="truncate text-base font-semibold tracking-tight">
                        <span v-if="activeChannel" class="text-muted-foreground">#</span>{{ activeChannel?.name ?? 'Select a channel' }}
                    </p>
                </div>
                <button
                    type="button"
                    class="-mr-2 flex size-11 shrink-0 items-center justify-center rounded-lg text-foreground"
                    aria-label="Open channels menu"
                    @click="mobileNavOpen = true"
                >
                    <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                    </svg>
                </button>
            </header>

            <MessagePane
                :channel="activeChannel"
                :messages="messages"
                :current-user-id="currentUser?.id ?? 0"
                @send="sendMessage"
            />
        </div>

        <!-- Mobile channel/workspace switcher -->
        <ChatMobileNav
            v-if="mobileNavOpen"
            :workspaces="workspaces"
            :active-workspace-id="activeWorkspaceId"
            :active-channel-id="activeChannelId"
            :online-count="onlineCount"
            :is-admin="isAdmin"
            @select-workspace="selectWorkspace"
            @select-channel="selectChannel"
            @close="mobileNavOpen = false"
        />
    </div>
</template>
