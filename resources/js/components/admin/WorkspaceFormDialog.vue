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

type AdminWorkspace = { id: number; name: string };

const props = defineProps<{
    mode: 'create' | 'edit';
    workspace?: AdminWorkspace | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ name: '' });

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }
    form.clearErrors();
    form.name =
        props.mode === 'edit' && props.workspace ? props.workspace.name : '';
});

function submit() {
    const onSuccess = () => {
        open.value = false;
    };

    if (props.mode === 'edit' && props.workspace) {
        form.patch(`/admin/workspaces/${props.workspace.id}`, {
            preserveScroll: true,
            onSuccess,
        });
    } else {
        form.post('/admin/workspaces', { preserveScroll: true, onSuccess });
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    mode === 'edit' ? 'Rename workspace' : 'New workspace'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        mode === 'edit'
                            ? 'Renaming keeps members, channels, and history intact.'
                            : 'A workspace groups channels and members for one client or project.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="workspace-name">Name</Label>
                    <Input
                        id="workspace-name"
                        v-model="form.name"
                        placeholder="Acme Corp"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        {{ mode === 'edit' ? 'Save' : 'Create workspace' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
