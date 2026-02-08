/**
 * Format a date string as Japanese date (YYYY/MM/DD)
 *
 * @param date - The date string to format
 * @returns Formatted date string in Japanese format (e.g., "2026/02/08")
 * @throws {RangeError} If date is invalid
 *
 * @example
 * formatDate('2026-02-08') // "2026/02/08"
 * formatDate('2026-12-31') // "2026/12/31"
 */
export const formatDate = (date: string): string => {
    const dateObj = new Date(date);
    if (isNaN(dateObj.getTime())) {
        throw new RangeError('Invalid date string');
    }
    return dateObj.toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};

/**
 * Format a date string as Japanese date and time (YYYY/MM/DD HH:MM:SS)
 *
 * @param date - The date string to format
 * @returns Formatted date-time string in Japanese format (e.g., "2026/02/08 14:30:45")
 * @throws {RangeError} If date is invalid
 *
 * @example
 * formatDateTime('2026-02-08T14:30:45') // "2026/02/08 14:30:45"
 */
export const formatDateTime = (date: string): string => {
    const dateObj = new Date(date);
    if (isNaN(dateObj.getTime())) {
        throw new RangeError('Invalid date string');
    }
    return dateObj.toLocaleString('ja-JP', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};
