<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';
import DesignGallery from '@/components/design/DesignGallery.vue';

/**
 * The dark variant matches `.dark *`, so a light sample can only render light
 * when no ancestor carries the class. The appearance composable puts it on
 * <html>, so this page suspends it and restores it on the way out.
 */
let restoreDark = false;

onMounted(() => {
    restoreDark = document.documentElement.classList.contains('dark');
    document.documentElement.classList.remove('dark');
});

onUnmounted(() => {
    document.documentElement.classList.toggle('dark', restoreDark);
});
</script>

<template>
    <Head title="Design system" />

    <div class="w-full">
        <DesignGallery theme="Mode clair" />
        <div class="dark">
            <DesignGallery theme="Mode sombre" />
        </div>
    </div>
</template>
