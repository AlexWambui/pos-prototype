<script setup lang="ts">
defineProps<{
    stat: number;
    label: string;
}>();
</script>

<template>
    <div class="stat border border-border p-4 rounded-lg space-y-0.5">
        <p class="text-[24px] font-bold">{{ stat }}</p>
        <p>{{ label }}</p>
        <div class="extras">
            <slot name="extras">
                <span class="text-sm text-muted-foreground">No extra info</span>
            </slot>
        </div>
    </div>
</template>