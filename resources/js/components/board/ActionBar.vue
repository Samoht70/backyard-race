<template>
    <div
        class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end sm:gap-3"
    >
        <p
            v-if="$slots.note"
            class="min-w-0 text-sm text-muted-foreground sm:mr-auto sm:max-w-prose"
        >
            <slot name="note" />
        </p>
        <slot />
    </div>
</template>
