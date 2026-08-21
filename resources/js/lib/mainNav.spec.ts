import { beforeEach, describe, expect, it, vi } from 'vitest';

const { page } = vi.hoisted(() => ({
    page: { props: {} as Record<string, unknown> },
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => page,
}));

const { BOTTOM_NAV_LIMIT, mainNavItems } = await import('@/lib/mainNav');
const { toUrl } = await import('@/lib/utils');

type Areas = {
    event?: boolean;
    documents?: boolean;
    registration?: boolean;
};

function signIn(areas: Areas, abilities: string[] = []): void {
    page.props = {
        auth: {
            permissions: Object.fromEntries(
                abilities.map((ability) => [ability, true]),
            ),
        },
        access: {
            event: areas.event === true,
            documents: areas.documents === true,
            registration: areas.registration === true,
        },
    };
}

function titles(): string[] {
    return mainNavItems().map((item) => item.title);
}

function bottomBarTitles(): string[] {
    return titles().slice(0, BOTTOM_NAV_LIMIT);
}

describe('mainNavItems', () => {
    beforeEach(() => {
        page.props = {};
    });

    it('offers a registered runner their registration, the briefing and the documents', () => {
        signIn({ event: true, documents: true, registration: true });

        expect(titles()).toEqual([
            'ui.nav.home',
            'ui.nav.registration',
            'ui.nav.briefing',
            'ui.nav.documents',
            'ui.nav.event',
        ]);
    });

    it('folds the event entry out of a registered runner bottom bar', () => {
        signIn({ event: true, documents: true, registration: true });

        expect(bottomBarTitles()).toEqual([
            'ui.nav.home',
            'ui.nav.registration',
            'ui.nav.briefing',
            'ui.nav.documents',
        ]);
    });

    it('withholds the briefing and the documents while the event is a draft', () => {
        signIn({ registration: true });

        expect(titles()).toEqual(['ui.nav.home', 'ui.nav.registration']);
    });

    it('withholds the registration entry from an account without one', () => {
        signIn({ event: true, documents: true });

        expect(titles()).toEqual([
            'ui.nav.home',
            'ui.nav.briefing',
            'ui.nav.documents',
            'ui.nav.event',
        ]);
    });

    it('keeps the management hub in a manager bottom bar', () => {
        signIn({ event: true, documents: true }, ['manage-event']);

        expect(bottomBarTitles()).toEqual([
            'ui.nav.home',
            'ui.nav.manage',
            'ui.nav.briefing',
            'ui.nav.documents',
        ]);
    });

    it('keeps the management hub ahead of a manager own registration', () => {
        signIn({ event: true, documents: true, registration: true }, [
            'manage-event',
        ]);

        expect(bottomBarTitles()).toEqual([
            'ui.nav.home',
            'ui.nav.manage',
            'ui.nav.registration',
            'ui.nav.briefing',
        ]);
    });

    it('withholds the management hub from a runner', () => {
        signIn({ event: true, documents: true, registration: true });

        expect(titles()).not.toContain('ui.nav.manage');
    });

    it('points every entry at its own screen', () => {
        signIn({ event: true, documents: true, registration: true }, [
            'manage-event',
        ]);

        expect(mainNavItems().map((item) => toUrl(item.href))).toEqual([
            '/dashboard',
            '/manage',
            '/registration',
            '/briefing',
            '/documents',
            '/event',
        ]);
    });
});
