<script setup lang="ts">
import BoardBand from '@/components/board/BoardBand.vue';
import BoardRail from '@/components/board/BoardRail.vue';
import Toaster from '@/components/Toaster.vue';
</script>

<template>
    <div class="flex h-svh flex-col overflow-hidden bg-background">
        <BoardBand />
        <BoardRail />
        <main
            scroll-region
            class="min-h-0 flex-1 overflow-y-auto overscroll-contain pb-safe"
        >
            <slot />
        </main>
        <Toaster position="top-center" />
    </div>
</template>
