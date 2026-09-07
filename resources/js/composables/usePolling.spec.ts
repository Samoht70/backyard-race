import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const { reload } = vi.hoisted(() => ({
    reload: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { reload },
}));

const { POLLING_INTERVAL_MS, usePolling } =
    await import('@/composables/usePolling');

type FakeDocument = {
    visibilityState: DocumentVisibilityState;
    addEventListener: (event: string, listener: () => void) => void;
    removeEventListener: (event: string, listener: () => void) => void;
    hide: () => void;
    show: () => void;
};

function fakeDocument(): FakeDocument {
    const listeners = new Set<() => void>();
    const state: { value: DocumentVisibilityState } = { value: 'visible' };

    return {
        get visibilityState() {
            return state.value;
        },
        addEventListener: (event, listener) => {
            if (event === 'visibilitychange') {
                listeners.add(listener);
            }
        },
        removeEventListener: (event, listener) => {
            if (event === 'visibilitychange') {
                listeners.delete(listener);
            }
        },
        hide: () => {
            state.value = 'hidden';
            listeners.forEach((listener) => listener());
        },
        show: () => {
            state.value = 'visible';
            listeners.forEach((listener) => listener());
        },
    };
}

describe('usePolling', () => {
    beforeEach(() => {
        reload.mockClear();
        vi.useFakeTimers();
        vi.stubGlobal('document', fakeDocument());
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('reloads the requested props on a fixed interval', () => {
        const { start } = usePolling(['tally', 'roundRunners']);

        start();
        vi.advanceTimersByTime(POLLING_INTERVAL_MS * 2);

        expect(reload).toHaveBeenCalledTimes(2);
        expect(reload).toHaveBeenCalledWith({
            only: ['tally', 'roundRunners'],
        });
    });

    it('suspends the refresh while the tab is in the background and resumes on return', () => {
        const { start } = usePolling(['tally']);
        const backgroundedDocument = document as unknown as FakeDocument;

        start();
        backgroundedDocument.hide();
        vi.advanceTimersByTime(POLLING_INTERVAL_MS * 3);

        expect(reload).not.toHaveBeenCalled();

        backgroundedDocument.show();
        vi.advanceTimersByTime(POLLING_INTERVAL_MS);

        expect(reload).toHaveBeenCalledTimes(1);
    });

    it('leaves no timer running once the screen tears down, as a session redirect does', () => {
        const { start, stop } = usePolling(['tally']);

        start();
        stop();
        vi.advanceTimersByTime(POLLING_INTERVAL_MS * 5);

        expect(reload).not.toHaveBeenCalled();
    });

    it('never polls a screen that starts already in the background', () => {
        const backgroundedDocument = fakeDocument();
        backgroundedDocument.hide();
        vi.stubGlobal('document', backgroundedDocument);

        const { start } = usePolling(['tally']);

        start();
        vi.advanceTimersByTime(POLLING_INTERVAL_MS * 3);

        expect(reload).not.toHaveBeenCalled();
    });
});
