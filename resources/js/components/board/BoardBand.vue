<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Separator } from 'reka-ui';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { t } from '@/lib/i18n';

const board = computed(() => usePage().props.board);

const places = computed(() => {
    const current = board.value?.confirmed_participants ?? 0;
    const limit = board.value?.max_participants;

    return limit === null || limit === undefined
        ? String(current)
        : `${current}/${limit}`;
});

const hasFirstStart = computed(() => board.value?.first_start_time !== null);
</script>

<template>
    <header
        class="shrink-0 flex-col gap-2 border-b-2 border-foreground px-4 py-3 sm:h-15 sm:flex-row sm:items-center sm:justify-between sm:gap-6 sm:py-0 sm:pr-6 sm:pl-6 lg:h-17 lg:px-8"
        :class="board ? 'flex' : 'hidden sm:flex'"
    >
        <AppLogo class="hidden min-w-0 sm:flex" />

        <dl
            v-if="board"
            class="flex shrink-0 gap-4 overflow-x-auto sm:items-center sm:gap-0 sm:overflow-visible"
        >
            <div class="shrink-0 sm:px-4 sm:first:pl-0 sm:last:pr-0">
                <dt
                    class="font-mono text-label text-muted-foreground uppercase"
                >
                    {{ t('ui.board.places') }}
                </dt>
                <dd class="font-mono text-figure tabular-nums">
                    {{ places }}
                </dd>
            </div>

            <template v-if="hasFirstStart">
                <Separator
                    orientation="vertical"
                    decorative
                    class="hidden h-8 w-px shrink-0 bg-border sm:block"
                />

                <div class="shrink-0 sm:px-4 sm:last:pr-0">
                    <dt
                        class="font-mono text-label text-muted-foreground uppercase"
                    >
                        {{ t('ui.board.first_start') }}
                    </dt>
                    <dd class="font-mono text-figure tabular-nums">
                        {{ board.first_start_time }}
                        <span
                            class="text-data font-normal text-muted-foreground"
                        >
                            {{ board.first_start_day }}
                        </span>
                    </dd>
                </div>
            </template>
        </dl>
    </header>
</template>
