import { CalendarDate, Time } from '@internationalized/date';

export function toCalendarDate(
    value?: string | null,
): CalendarDate | undefined {
    const parts = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value ?? '');

    if (parts === null) {
        return undefined;
    }

    return new CalendarDate(
        Number(parts[1]),
        Number(parts[2]),
        Number(parts[3]),
    );
}

export function toTime(value?: string | null): Time | undefined {
    const parts = /^(\d{2}):(\d{2})(?::(\d{2}))?$/.exec(value ?? '');

    if (parts === null) {
        return undefined;
    }

    return new Time(Number(parts[1]), Number(parts[2]));
}
