import type { ComputedRef, Ref } from 'vue';
import { computed, nextTick, ref } from 'vue';

export type UseFormStepsReturn = {
    current: Ref<number>;
    isFirst: ComputedRef<boolean>;
    isLast: ComputedRef<boolean>;
    goTo: (step: number, field?: string | null) => Promise<void>;
    next: () => void;
    back: () => void;
};

export function useFormSteps(
    total: number,
    container: Ref<HTMLElement | null>,
): UseFormStepsReturn {
    const current = ref(0);
    const isFirst = computed(() => current.value === 0);
    const isLast = computed(() => current.value === total - 1);

    function stepElement(step: number): HTMLElement | null {
        return (
            container.value?.querySelector<HTMLElement>(
                `[data-step="${step}"]`,
            ) ?? null
        );
    }

    function fieldElement(field: string): HTMLElement | null {
        return (
            container.value?.querySelector<HTMLElement>(`[name="${field}"]`) ??
            null
        );
    }

    function invalidControl(step: number): boolean {
        const controls = stepElement(step)?.querySelectorAll<
            HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
        >('input, select, textarea');

        return Array.from(controls ?? []).some(
            (control) => !control.reportValidity(),
        );
    }

    async function goTo(
        step: number,
        field: string | null = null,
    ): Promise<void> {
        if (step < 0 || step >= total) {
            return;
        }

        current.value = step;

        await nextTick();

        const target =
            (field === null ? null : fieldElement(field)) ?? stepElement(step);

        target?.focus();
    }

    function next(): void {
        if (invalidControl(current.value)) {
            return;
        }

        void goTo(current.value + 1);
    }

    function back(): void {
        void goTo(current.value - 1);
    }

    return { current, isFirst, isLast, goTo, next, back };
}
