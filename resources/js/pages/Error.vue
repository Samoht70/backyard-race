<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import ActionButton from '@/components/ActionButton.vue';
import BoardPage from '@/components/board/BoardPage.vue';
import EmptyState from '@/components/state/EmptyState.vue';
import {
    errorDescriptionKey,
    errorReturnItem,
    errorSituation,
    errorSituationIcons,
    errorTitleKey,
} from '@/lib/errorSituation';
import { t } from '@/lib/i18n';

type Props = {
    status: number;
};

const props = defineProps<Props>();

const situation = computed(() => errorSituation(props.status));
const title = computed(() => t(errorTitleKey(situation.value)));
const description = computed(() => t(errorDescriptionKey(situation.value)));
const icon = computed(() => errorSituationIcons[situation.value]);
const back = computed(() => errorReturnItem());
</script>

<template>
    <Head :title="title" />

    <BoardPage>
        <EmptyState :icon="icon" :title="title" :description="description">
            <template #action>
                <ActionButton as-child>
                    <Link :href="back.href">{{ back.title }}</Link>
                </ActionButton>
            </template>
        </EmptyState>
    </BoardPage>
</template>
