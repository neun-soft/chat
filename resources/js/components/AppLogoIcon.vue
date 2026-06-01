<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <text
            x="50%"
            y="53%"
            dominant-baseline="central"
            text-anchor="middle"
            font-family="'DM Sans', system-ui, sans-serif"
            font-size="17"
            font-weight="700"
            fill="currentColor"
        >
            N
        </text>
    </svg>
</template>
