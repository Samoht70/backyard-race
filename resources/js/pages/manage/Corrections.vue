<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { CircleSlash, Undo2 } from '@lucide/vue';
import { computed } from 'vue';
import BoardPage from '@/components/board/BoardPage.vue';
import Notice from '@/components/Notice.vue';
import CorrectionList from '@/components/race/CorrectionList.vue';
import LapReinstatementDialog from '@/components/race/LapReinstatementDialog.vue';
import LapReversionDialog from '@/components/race/LapReversionDialog.vue';
import { t } from '@/lib/i18n';
import type { CorrectableLap } from '@/types/race';

type Props = {
    reinstatable: CorrectableLap[];
    revertable: CorrectableLap[];
};

defineProps<Props>();

const page = usePage();

const refusal = computed(() => page.props.errors.lap);
</script>

<template>
    <Head :title="t('race.correction.title')" />

    <BoardPage>
        <div class="grid gap-6">
            <div class="grid gap-2">
                <h1 class="text-title">{{ t('race.correction.title') }}</h1>
                <p class="max-w-[64ch] text-sm text-muted-foreground">
                    {{ t('race.correction.lead') }}
                </p>
            </div>

            <Notice
                v-if="refusal"
                :title="t('race.correction.refused')"
                tone="danger"
            >
                {{ refusal }}
            </Notice>

            <CorrectionList
                :title="t('race.correction.reinstate_section')"
                :icon="Undo2"
                :empty="t('race.correction.reinstate_empty')"
                :laps="reinstatable"
            >
                <template #action="{ lap }">
                    <LapReinstatementDialog :lap="lap" />
                </template>
            </CorrectionList>

            <CorrectionList
                :title="t('race.correction.revert_section')"
                :icon="CircleSlash"
                :empty="t('race.correction.revert_empty')"
                :laps="revertable"
            >
                <template #action="{ lap }">
                    <LapReversionDialog :lap="lap" />
                </template>
            </CorrectionList>
        </div>
    </BoardPage>
</template>
