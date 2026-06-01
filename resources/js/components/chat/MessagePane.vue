<script setup lang="ts">
import type { ChatChannel, ChatMessage } from '@/types';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps<{
    channel: ChatChannel | null;
    messages: ChatMessage[];
    currentUserId: number;
}>();

const emit = defineEmits<{
    (e: 'send', payload: { body: string; image: File | null }): void;
}>();

const draft = ref('');
const scroller = ref<HTMLElement | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const selectedImage = ref<File | null>(null);
const previewUrl = ref<string | null>(null);

const canSend = computed(() => Boolean(draft.value.trim() || selectedImage.value));

function pickImage() {
    fileInput.value?.click();
}

function onFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    clearPreview();
    if (file) {
        selectedImage.value = file;
        previewUrl.value = URL.createObjectURL(file);
    }
}

function clearPreview() {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
    selectedImage.value = null;
    if (fileInput.value) fileInput.value.value = '';
}

function send() {
    const body = draft.value.trim();
    if (!body && !selectedImage.value) return;
    emit('send', { body, image: selectedImage.value });
    draft.value = '';
    clearPreview();
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
                    <p
                        v-if="message.body"
                        class="whitespace-pre-wrap break-words text-[15px] leading-relaxed text-foreground/90 md:text-sm"
                    >
                        {{ message.body }}
                    </p>
                    <a
                        v-if="message.image_url"
                        :href="message.image_url"
                        target="_blank"
                        rel="noopener"
                        class="mt-1 block w-fit"
                    >
                        <img
                            :src="message.image_url"
                            alt="attachment"
                            class="max-h-80 max-w-full rounded-lg border border-border object-cover"
                        />
                    </a>
                </div>

                <p v-if="!messages.length" class="text-sm text-muted-foreground">
                    No messages yet. Say hello.
                </p>
            </div>

            <div class="shrink-0 border-t border-border p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] md:pb-3">
                <!-- Selected image preview -->
                <div v-if="previewUrl" class="relative mb-2 w-fit">
                    <img :src="previewUrl" alt="preview" class="max-h-32 rounded-lg border border-border" />
                    <button
                        type="button"
                        class="absolute -right-2 -top-2 flex size-6 items-center justify-center rounded-full bg-foreground text-background"
                        aria-label="Remove image"
                        @click="clearPreview"
                    >
                        <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex items-end gap-2 rounded-xl border border-border bg-background px-2 py-2 focus-within:ring-1 focus-within:ring-ring">
                    <input
                        ref="fileInput"
                        type="file"
                        accept="image/jpeg,image/png,image/gif,image/webp"
                        class="hidden"
                        @change="onFileChange"
                    />
                    <button
                        type="button"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        aria-label="Attach image"
                        @click="pickImage"
                    >
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                        </svg>
                    </button>
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
                        :disabled="!canSend"
                        @click="send"
                    >
                        Send
                    </button>
                </div>
            </div>
        </template>
    </section>
</template>
