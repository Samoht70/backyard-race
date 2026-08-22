<script setup lang="ts">
import { FileText } from '@lucide/vue';
import { computed } from 'vue';
import { formatFileSize } from '@/lib/fileSize';
import { t } from '@/lib/i18n';
import type { EventDocument } from '@/types/document';

type Props = {
    document: EventDocument;
};

const props = defineProps<Props>();

const meta = computed(() =>
    [
        props.document.file_name,
        props.document.size === null
            ? null
            : formatFileSize(props.document.size),
    ]
        .filter(Boolean)
        .join(' · '),
);
</script>

<template>
    <div
        class="flex min-h-[4.25rem] min-w-0 items-center gap-3 rounded-sm border border-border bg-card px-3 py-2.5"
    >
        <FileText
            class="size-5 shrink-0 text-muted-foreground"
            aria-hidden="true"
        />

        <div class="flex min-w-0 flex-1 flex-col justify-center gap-px">
            <a
                v-if="document.url"
                :href="document.url"
                class="truncate font-semibold underline-offset-4 hover:underline"
                :aria-label="
                    t('document.download_aria', { title: document.title })
                "
            >
                {{ document.title }}
            </a>
            <span v-else class="truncate font-semibold">
                {{ document.title }}
            </span>

            <span
                v-if="document.description"
                class="truncate text-sm text-muted-foreground"
            >
                {{ document.description }}
            </span>

            <span
                v-if="meta"
                class="truncate font-mono text-data text-muted-foreground"
            >
                {{ meta }}
            </span>
        </div>

        <slot name="cell" />
    </div>
</template>
