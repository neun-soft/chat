<script setup lang="ts">
defineProps<{
    title?: string;
}>();
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-neutral-950">
        <div class="w-full text-center">
            <slot />
        </div>
    </div>
</template>
