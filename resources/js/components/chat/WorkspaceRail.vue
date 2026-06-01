<script setup lang="ts">
import type { ChatWorkspace } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    workspaces: ChatWorkspace[];
    activeId: number | null;
    isAdmin: boolean;
}>();

const emit = defineEmits<{
    (e: 'select', id: number): void;
}>();

function initials(name: string): string {
    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase() ?? '')
        .join('');
}

const sorted = computed(() => props.workspaces);
</script>

<template>
    <nav
        class="h-full w-16 flex-col items-center gap-2 border-r border-border bg-sidebar py-3"
    >
        <button
            v-for="w in sorted"
            :key="w.id"
            type="button"
            :title="w.name"
            class="flex size-11 items-center justify-center rounded-2xl text-sm font-semibold transition-all hover:rounded-xl"
            :class="
                w.id === activeId
                    ? 'rounded-xl bg-primary text-primary-foreground'
                    : 'bg-muted text-muted-foreground hover:bg-muted/70'
            "
            @click="emit('select', w.id)"
        >
            {{ initials(w.name) }}
        </button>

        <a
            v-if="isAdmin"
            href="/admin"
            title="Admin"
            class="mt-auto flex size-11 items-center justify-center rounded-2xl border border-dashed border-border text-muted-foreground transition-all hover:rounded-xl hover:text-foreground"
        >
            <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 12h9.75m-9.75 6h9.75M3.75 6h.007v.008H3.75V6Zm.375 6a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 6h.007v.008H3.75V18Z" />
            </svg>
        </a>
    </nav>
</template>
