import { beforeEach, describe, expect, it, vi } from 'vitest';

const { page } = vi.hoisted(() => ({
    page: { props: {} as Record<string, unknown> },
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => page,
}));

const { mainNavItems } = await import('@/lib/mainNav');
const { toUrl } = await import('@/lib/utils');

type Areas = {
    event?: boolean;
    documents?: boolean;
    registration?: boolean;
    register?: boolean;
    standings?: boolean;
    results?: boolean;
};

function share(
    areas: Areas,
    user: { id: number } | null,
    abilities: string[],
): void {
    page.props = {
        auth: {
            user,
            permissions: Object.fromEntries(
                abilities.map((ability) => [ability, true]),
            ),
        },
        access: {
            event: areas.event === true,
            documents: areas.documents === true,
            registration: areas.registration === true,
            register: areas.register === true,
            standings: areas.standings === true,
            results: areas.results === true,
        },
    };
}

function signIn(areas: Areas, abilities: string[] = []): void {
    share(areas, { id: 1 }, abilities);
}

function visitAsGuest(areas: Areas): void {
    share(areas, null, []);
}

function titles(): string[] {
    return mainNavItems().map((item) => item.title);
}

describe('mainNavItems', () => {
    beforeEach(() => {
        page.props = {};
    });

    it('offers a guest the race, the documents, a way in and a way to register', () => {
        visitAsGuest({ event: true, documents: true, register: true });

        expect(titles()).toEqual([
            'ui.nav.event',
            'ui.nav.documents',
            'ui.nav.registration',
            'ui.nav.register',
        ]);
    });

    it('offers a guest the standings once the race is closed', () => {
        visitAsGuest({ event: true, documents: true, standings: true });

        expect(titles()).toEqual([
            'ui.nav.event',
            'ui.nav.documents',
            'ui.nav.standings',
            'ui.nav.registration',
        ]);
    });

    it('offers a signed-in runner the standings once the race is closed', () => {
        signIn({ event: true, documents: true, standings: true });

        expect(titles()).toEqual([
            'ui.nav.home',
            'ui.nav.briefing',
            'ui.nav.documents',
            'ui.nav.standings',
            'ui.nav.event',
        ]);
    });

    it('renames the home entry once the home page carries the results', () => {
        visitAsGuest({
            event: true,
            documents: true,
            standings: true,
            results: true,
        });

        expect(titles()).toEqual([
            'ui.nav.results',
            'ui.nav.documents',
            'ui.nav.standings',
            'ui.nav.registration',
        ]);
    });

    it('renames the home entry for a signed-in runner too', () => {
        signIn({
            event: true,
            documents: true,
            standings: true,
            results: true,
        });

        expect(titles()).toEqual([
            'ui.nav.home',
            'ui.nav.briefing',
            'ui.nav.documents',
            'ui.nav.standings',
            'ui.nav.results',
        ]);
    });

    it('withholds the standings while the race is still running', () => {
        visitAsGuest({ event: true, documents: true });

        expect(titles()).not.toContain('ui.nav.standings');
    });

    it('withholds the account creation from a guest once the window is shut', () => {
        visitAsGuest({ event: true, documents: true });

        expect(titles()).not.toContain('ui.nav.register');
    });

    it('keeps the race entry for a guest while the event is a draft', () => {
        visitAsGuest({});

        expect(titles()).toEqual(['ui.nav.event', 'ui.nav.registration']);
    });

    it('points a guest at the public race page and the login screen', () => {
        visitAsGuest({ event: true, documents: true, register: true });

        expect(mainNavItems().map((item) => toUrl(item.href))).toEqual([
            '/',
            '/documents',
            '/login',
            '/account/create',
        ]);
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

    it('opens a manager rail on the management hub', () => {
        signIn({ event: true, documents: true }, ['manage-event']);

        expect(titles()).toEqual([
            'ui.nav.home',
            'ui.nav.manage',
            'ui.nav.briefing',
            'ui.nav.documents',
            'ui.nav.event',
        ]);
    });

    it('keeps the management hub ahead of a manager own registration', () => {
        signIn({ event: true, documents: true, registration: true }, [
            'manage-event',
        ]);

        expect(titles()).toEqual([
            'ui.nav.home',
            'ui.nav.manage',
            'ui.nav.registration',
            'ui.nav.briefing',
            'ui.nav.documents',
            'ui.nav.event',
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
            '/',
        ]);
    });
});
