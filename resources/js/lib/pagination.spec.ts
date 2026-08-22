import { describe, expect, it } from 'vitest';
import { paginationBar, windowedPages } from '@/lib/pagination';

const hrefForPage = (page: number) => `/manage/registrations?page=${page}`;

describe('windowedPages', () => {
    it('keeps every page while they fit in the window', () => {
        expect(windowedPages({ current_page: 1, last_page: 3 })).toEqual([
            1, 2, 3,
        ]);
    });

    it('centres the window on the current page', () => {
        expect(windowedPages({ current_page: 5, last_page: 10 })).toEqual([
            3, 4, 5, 6, 7,
        ]);
    });

    it('holds the window against the first page', () => {
        expect(windowedPages({ current_page: 2, last_page: 10 })).toEqual([
            1, 2, 3, 4, 5,
        ]);
    });

    it('holds the window against the last page', () => {
        expect(windowedPages({ current_page: 10, last_page: 10 })).toEqual([
            6, 7, 8, 9, 10,
        ]);
    });

    it('keeps a single page list on a single page', () => {
        expect(windowedPages({ current_page: 1, last_page: 1 })).toEqual([1]);
    });
});

describe('paginationBar', () => {
    it('marks the current page and no other', () => {
        const bar = paginationBar(
            { current_page: 2, last_page: 3 },
            hrefForPage,
        );

        expect(bar.pages.map((link) => link.current)).toEqual([
            false,
            true,
            false,
        ]);
    });

    it('offers no previous page on the first page', () => {
        const bar = paginationBar(
            { current_page: 1, last_page: 3 },
            hrefForPage,
        );

        expect(bar.previous).toBeNull();
        expect(bar.next?.page).toBe(2);
    });

    it('offers no next page on the last page', () => {
        const bar = paginationBar(
            { current_page: 3, last_page: 3 },
            hrefForPage,
        );

        expect(bar.next).toBeNull();
        expect(bar.previous?.page).toBe(2);
    });

    it('builds every link through the given route', () => {
        const bar = paginationBar(
            { current_page: 1, last_page: 2 },
            hrefForPage,
        );

        expect(bar.pages.map((link) => link.href)).toEqual([
            '/manage/registrations?page=1',
            '/manage/registrations?page=2',
        ]);
    });
});
