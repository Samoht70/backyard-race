import type { Href, PageLink, Pagination } from '@/types/ui';

export const PAGINATION_WINDOW = 5;

export type PaginationBar = {
    pages: PageLink[];
    previous: PageLink | null;
    next: PageLink | null;
};

export function windowedPages(pagination: Pagination): number[] {
    const first = Math.max(
        1,
        Math.min(
            pagination.current_page - Math.floor(PAGINATION_WINDOW / 2),
            pagination.last_page - PAGINATION_WINDOW + 1,
        ),
    );
    const last = Math.min(pagination.last_page, first + PAGINATION_WINDOW - 1);

    return Array.from({ length: last - first + 1 }, (_, step) => first + step);
}

export function paginationBar(
    pagination: Pagination,
    hrefForPage: (page: number) => Href,
): PaginationBar {
    const link = (page: number): PageLink => ({
        page,
        href: hrefForPage(page),
        current: page === pagination.current_page,
    });

    const isFirst = pagination.current_page <= 1;
    const isLast = pagination.current_page >= pagination.last_page;

    return {
        pages: windowedPages(pagination).map(link),
        previous: isFirst ? null : link(pagination.current_page - 1),
        next: isLast ? null : link(pagination.current_page + 1),
    };
}
