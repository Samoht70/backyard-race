<script setup lang="ts">
import BoardPage from '@/components/board/BoardPage.vue';
import ManageDesks from '@/components/manage/ManageDesks.vue';
</script>

<template>
    <BoardPage>
        <div class="grid gap-6">
            <ManageDesks />
            <slot />
        </div>
    </BoardPage>
</template>
