import { beforeEach, describe, expect, it, vi } from 'vitest';

const { page } = vi.hoisted(() => ({
    page: { props: {} as Record<string, unknown> },
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => page,
}));

const { errorReturnItem, errorSituation } =
    await import('@/lib/errorSituation');
const { toUrl } = await import('@/lib/utils');

function share(user: { id: number } | null): void {
    page.props = {
        auth: { user, permissions: {} },
        translations: {
            'error.back_home': 'Revenir à l’accueil',
            'error.back_dashboard': 'Revenir à mon espace',
        },
    };
}

describe('errorSituation', () => {
    it('names the four situations rendered in the site', () => {
        expect(errorSituation(404)).toBe('not_found');
        expect(errorSituation(403)).toBe('forbidden');
        expect(errorSituation(419)).toBe('expired');
        expect(errorSituation(500)).toBe('server');
    });

    it('falls back to the server situation on any other status', () => {
        expect(errorSituation(502)).toBe('server');
    });
});

describe('errorReturnItem', () => {
    beforeEach(() => {
        page.props = {};
    });

    it('sends a guest back to the home page', () => {
        share(null);

        const item = errorReturnItem();

        expect(item.title).toBe('Revenir à l’accueil');
        expect(toUrl(item.href)).toBe('/');
    });

    it('sends a signed-in runner back to their own space', () => {
        share({ id: 1 });

        const item = errorReturnItem();

        expect(item.title).toBe('Revenir à mon espace');
        expect(toUrl(item.href)).toBe('/dashboard');
    });
});
