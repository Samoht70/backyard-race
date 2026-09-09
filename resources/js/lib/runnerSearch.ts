export function meetsSearchThreshold(term: string): boolean {
    const trimmed = term.trim();

    if (trimmed.length >= 2) {
        return true;
    }

    return trimmed.length === 1 && /^[0-9]$/.test(trimmed);
}
