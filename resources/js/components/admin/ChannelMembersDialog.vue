<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

type Member = { id: number; name: string; email: string; role: string };
type AdminChannel = { id: number; slug: string; member_ids: number[] };

const props = defineProps<{
    channel: AdminChannel;
    members: Member[];
}>();

const open = defineModel<boolean>('open', { default: false });

const selected = ref<Set<number>>(new Set());
const saving = ref(false);

watch(open, (isOpen) => {
    if (isOpen) {
        selected.value = new Set(props.channel.member_ids);
    }
});

function toggle(id: number) {
    const next = new Set(selected.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    selected.value = next;
}

function selectAll() {
    selected.value = new Set(props.members.map((m) => m.id));
}

function selectNone() {
    selected.value = new Set();
}

function save() {
    saving.value = true;
    router.post(
        `/admin/channels/${props.channel.id}/members`,
        { member_ids: [...selected.value] },
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Members of #{{ channel.slug }}</DialogTitle>
                <DialogDescription>
                    Choose who can see and post in this channel. Only workspace
                    members are listed.
                </DialogDescription>
            </DialogHeader>

            <div class="flex items-center justify-between text-sm">
                <span class="text-muted-foreground"
                    >{{ selected.size }} of {{ members.length }} selected</span
                >
                <div class="flex gap-3">
                    <button
                        type="button"
                        class="text-primary hover:underline"
                        @click="selectAll"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        class="text-muted-foreground hover:underline"
                        @click="selectNone"
                    >
                        None
                    </button>
                </div>
            </div>

            <div
                class="max-h-72 space-y-1 overflow-y-auto rounded-md border border-border p-1"
            >
                <Label
                    v-for="m in members"
                    :key="m.id"
                    class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 font-normal hover:bg-muted"
                >
                    <Checkbox
                        :model-value="selected.has(m.id)"
                        @update:model-value="toggle(m.id)"
                    />
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate">{{ m.name }}</span>
                        <span class="truncate text-xs text-muted-foreground">{{
                            m.email
                        }}</span>
                    </span>
                </Label>
                <p
                    v-if="members.length === 0"
                    class="px-2 py-4 text-center text-sm text-muted-foreground"
                >
                    No workspace members yet.
                </p>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="open = false"
                    >Cancel</Button
                >
                <Button type="button" :disabled="saving" @click="save"
                    >Save members</Button
                >
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
