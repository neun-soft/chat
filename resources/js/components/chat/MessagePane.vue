<script setup lang="ts">
import type { ChatChannel, ChatMessage } from '@/types';
import { nextTick, ref, watch } from 'vue';

const props = defineProps<{
    channel: ChatChannel | null;
    messages: ChatMessage[];
    currentUserId: number;
}>();

const emit = defineEmits<{
    (e: 'send', body: string): void;
}>();

const draft = ref('');
const scroller = ref<HTMLElement | null>(null);

function send() {
    const body = draft.value.trim();
    if (!body) return;
    emit('send', body);
    draft.value = '';
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        send();
    }
}

function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function isGrouped(index: number): boolean {
    if (index === 0) return false;
    const prev = props.messages[index - 1];
    const curr = props.messages[index];
    return (
        prev.user.id === curr.user.id &&
        new Date(curr.created_at).getTime() - new Date(prev.created_at).getTime() < 5 * 60 * 1000
    );
}

async function scrollToBottom() {
    await nextTick();
    if (scroller.value) {
        scroller.value.scrollTop = scroller.value.scrollHeight;
    }
}

watch(
    () => props.messages.length,
    () => scrollToBottom(),
);
watch(
    () => props.channel?.id,
    () => scrollToBottom(),
);
</script>

<template>
    <section class="flex h-full min-w-0 flex-1 flex-col bg-background">
        <header class="hidden h-14 shrink-0 items-center gap-2 border-b border-border px-5 md:flex">
            <template v-if="channel">
                <span class="text-lg text-muted-foreground">#</span>
                <h2 class="font-semibold tracking-tight">{{ channel.name }}</h2>
                <span v-if="channel.topic" class="truncate text-sm text-muted-foreground">
                    — {{ channel.topic }}
                </span>
            </template>
        </header>

        <div v-if="!channel" class="flex flex-1 items-center justify-center text-muted-foreground">
            Select a channel to start chatting.
        </div>

        <template v-else>
            <div ref="scroller" class="flex-1 overflow-y-auto px-4 py-4 md:px-5">
                <div
                    v-for="(message, index) in messages"
                    :key="message.id"
                    class="group"
                    :class="isGrouped(index) ? 'mt-0.5' : 'mt-4 first:mt-0'"
                >
                    <div v-if="!isGrouped(index)" class="flex items-baseline gap-2">
                        <span class="font-semibold">{{ message.user.name }}</span>
                        <span class="text-xs text-muted-foreground">{{ formatTime(message.created_at) }}</span>
                    </div>
                    <p class="whitespace-pre-wrap break-words text-[15px] leading-relaxed text-foreground/90 md:text-sm">
                        {{ message.body }}
                    </p>
                </div>

                <p v-if="!messages.length" class="text-sm text-muted-foreground">
                    No messages yet. Say hello.
                </p>
            </div>

            <div class="shrink-0 border-t border-border p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] md:pb-3">
                <div class="flex items-end gap-2 rounded-xl border border-border bg-background px-3 py-2 focus-within:ring-1 focus-within:ring-ring">
                    <textarea
                        v-model="draft"
                        rows="1"
                        :placeholder="`Message #${channel.name}`"
                        class="max-h-40 min-h-6 flex-1 resize-none bg-transparent text-base outline-none placeholder:text-muted-foreground md:text-sm"
                        @keydown="onKeydown"
                    ></textarea>
                    <button
                        type="button"
                        class="shrink-0 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground transition-opacity disabled:opacity-40 md:py-1.5"
                        :disabled="!draft.trim()"
                        @click="send"
                    >
                        Send
                    </button>
                </div>
            </div>
        </template>
    </section>
</template>
