<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type AdminChannel = { id: number; name: string; topic: string | null };

const props = defineProps<{
    mode: 'create' | 'edit';
    workspaceId: number;
    memberIds?: number[];
    channel?: AdminChannel | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm<{ name: string; topic: string; member_ids: number[] }>({
    name: '',
    topic: '',
    member_ids: [],
});

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }
    form.clearErrors();
    if (props.mode === 'edit' && props.channel) {
        form.name = props.channel.name;
        form.topic = props.channel.topic ?? '';
    } else {
        form.name = '';
        form.topic = '';
        // New channels start with every current workspace member.
        form.member_ids = props.memberIds ?? [];
    }
});

function submit() {
    const onSuccess = () => {
        open.value = false;
    };

    if (props.mode === 'edit' && props.channel) {
        form.patch(`/admin/channels/${props.channel.id}`, {
            preserveScroll: true,
            onSuccess,
        });
    } else {
        form.post(`/admin/workspaces/${props.workspaceId}/channels`, {
            preserveScroll: true,
            onSuccess,
        });
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    mode === 'edit' ? 'Edit channel' : 'New channel'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        mode === 'edit'
                            ? 'Update the channel name or topic.'
                            : 'New channels include every current workspace member. You can adjust membership afterwards.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="channel-name">Name</Label>
                    <Input
                        id="channel-name"
                        v-model="form.name"
                        placeholder="general"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="channel-topic"
                        >Topic
                        <span class="text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <Input
                        id="channel-topic"
                        v-model="form.topic"
                        placeholder="What’s this channel about?"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.topic" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        {{ mode === 'edit' ? 'Save' : 'Create channel' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
