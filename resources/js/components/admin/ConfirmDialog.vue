<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        destructive?: boolean;
    }>(),
    {
        title: 'Are you sure?',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        destructive: true,
    },
);

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ confirm: [] }>();

function confirm() {
    emit('confirm');
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">{{
                    description
                }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="open = false">{{
                    cancelLabel
                }}</Button>
                <Button
                    :variant="destructive ? 'destructive' : 'default'"
                    @click="confirm"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
