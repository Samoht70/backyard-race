<template>
    <div
        class="grid items-start gap-6 sm:grid-cols-2 lg:grid-cols-12 lg:gap-12"
    >
        <div class="grid content-start gap-6 lg:col-span-5">
            <slot name="lead" />
        </div>
        <div class="grid content-start gap-6 lg:col-span-7">
            <slot />
        </div>
    </div>
</template>
