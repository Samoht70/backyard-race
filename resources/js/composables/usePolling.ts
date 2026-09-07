import { router } from '@inertiajs/vue3';

export const POLLING_INTERVAL_MS = 15_000;

export type UsePollingReturn = {
    start: () => void;
    stop: () => void;
};

export function usePolling(only: string[]): UsePollingReturn {
    let timer: ReturnType<typeof setInterval> | null = null;

    function reload(): void {
        router.reload({ only });
    }

    function startTimer(): void {
        if (timer !== null) {
            return;
        }

        timer = setInterval(reload, POLLING_INTERVAL_MS);
    }

    function stopTimer(): void {
        if (timer === null) {
            return;
        }

        clearInterval(timer);
        timer = null;
    }

    function handleVisibilityChange(): void {
        if (document.visibilityState === 'hidden') {
            stopTimer();
        } else {
            startTimer();
        }
    }

    function start(): void {
        document.addEventListener('visibilitychange', handleVisibilityChange);

        if (document.visibilityState !== 'hidden') {
            startTimer();
        }
    }

    function stop(): void {
        document.removeEventListener(
            'visibilitychange',
            handleVisibilityChange,
        );
        stopTimer();
    }

    return { start, stop };
}
