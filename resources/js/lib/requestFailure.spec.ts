import { beforeEach, describe, expect, it, vi } from 'vitest';

const { handlers, errors, page } = vi.hoisted(() => ({
    handlers: {} as Record<string, () => void>,
    errors: [] as string[],
    page: { props: {} as Record<string, unknown> },
}));

vi.mock('@inertiajs/vue3', () => ({
    router: {
        on: (event: string, handler: () => void) => {
            handlers[event] = handler;
        },
    },
    usePage: () => page,
}));

vi.mock('vue-sonner', () => ({
    toast: {
        error: (message: string) => errors.push(message),
    },
}));

const { initializeRequestFailureToast } = await import('@/lib/requestFailure');

describe('initializeRequestFailureToast', () => {
    beforeEach(() => {
        errors.length = 0;
        page.props = {
            translations: {
                'ui.state.unreachable': 'Le geste n’est pas parti',
            },
        };
    });

    it('tells the manager when a press never reached the server', () => {
        initializeRequestFailureToast();
        handlers.networkError();

        expect(errors).toEqual(['Le geste n’est pas parti']);
    });

    it('says nothing until a request actually fails', () => {
        initializeRequestFailureToast();

        expect(errors).toEqual([]);
    });
});
