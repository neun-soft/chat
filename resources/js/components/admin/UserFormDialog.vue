<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type AdminUser = { id: number; name: string; email: string; is_admin: boolean };

const props = defineProps<{
    mode: 'create' | 'edit';
    user?: AdminUser | null;
}>();

const open = defineModel<boolean>('open', { default: false });

const form = useForm({ name: '', email: '', password: '', is_admin: false });

// Hydrate the form whenever the dialog opens so create/edit always start clean.
watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }
    form.clearErrors();
    if (props.mode === 'edit' && props.user) {
        form.name = props.user.name;
        form.email = props.user.email;
        form.password = '';
        form.is_admin = props.user.is_admin;
    } else {
        form.reset();
    }
});

function submit() {
    const onSuccess = () => {
        open.value = false;
    };

    if (props.mode === 'edit' && props.user) {
        form.patch(`/admin/users/${props.user.id}`, {
            preserveScroll: true,
            onSuccess,
        });
    } else {
        form.post('/admin/users', { preserveScroll: true, onSuccess });
    }
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{
                    mode === 'edit' ? 'Edit login' : 'New login'
                }}</DialogTitle>
                <DialogDescription>
                    {{
                        mode === 'edit'
                            ? 'Update this person’s details. Leave the password blank to keep it unchanged.'
                            : 'Create a login for a teammate or client. They sign in with this email and password.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="user-name">Name</Label>
                    <Input
                        id="user-name"
                        v-model="form.name"
                        placeholder="Jane Doe"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="user-email">Email</Label>
                    <Input
                        id="user-email"
                        v-model="form.email"
                        type="email"
                        placeholder="jane@example.com"
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="user-password">Password</Label>
                    <Input
                        id="user-password"
                        v-model="form.password"
                        type="text"
                        :placeholder="
                            mode === 'edit'
                                ? 'Leave blank to keep current'
                                : 'At least 8 characters'
                        "
                        autocomplete="off"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <Label
                    class="flex items-center gap-2 font-normal text-muted-foreground"
                >
                    <Checkbox v-model="form.is_admin" />
                    Admin — full access to this console
                </Label>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing">
                        {{ mode === 'edit' ? 'Save changes' : 'Create login' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
