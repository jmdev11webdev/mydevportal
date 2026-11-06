export function parseDate(value: string | null | undefined): Date | null {
    if (!value) {
        return null;
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatDate(
    value: string | null | undefined,
    options: Intl.DateTimeFormatOptions = {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    },
): string {
    const date = parseDate(value);

    if (!date) {
        return 'No date';
    }

    return new Intl.DateTimeFormat(undefined, options).format(date);
}

export function formatShortDate(value: string | null | undefined): string {
    return formatDate(value, { month: 'short', day: 'numeric' });
}

export function isOverdue(value: string | null | undefined): boolean {
    const date = parseDate(value);

    if (!date) {
        return false;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return date.getTime() < today.getTime();
}

export function isToday(value: string | null | undefined): boolean {
    const date = parseDate(value);

    if (!date) {
        return false;
    }

    const today = new Date();

    return date.toDateString() === today.toDateString();
}

export function relativeDate(value: string | null | undefined): string {
    const date = parseDate(value);

    if (!date) {
        return '';
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const target = new Date(date);
    target.setHours(0, 0, 0, 0);

    const diff = Math.round(
        (target.getTime() - today.getTime()) / (1000 * 60 * 60 * 24),
    );

    if (diff === 0) {
        return 'Today';
    }

    if (diff === 1) {
        return 'Tomorrow';
    }

    if (diff === -1) {
        return 'Yesterday';
    }

    if (diff < 0) {
        return `${Math.abs(diff)} days overdue`;
    }

    if (diff < 7) {
        return `In ${diff} days`;
    }

    return formatShortDate(value);
}

export function initials(name: string | null | undefined): string {
    if (!name) {
        return '?';
    }

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase() ?? '')
        .join('');
}

export function toDateInput(value: string | null | undefined): string {
    const date = parseDate(value);

    if (!date) {
        return '';
    }

    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export function todayInput(): string {
    const date = new Date();
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');

    return `${year}-${month}-${day}`;
}
