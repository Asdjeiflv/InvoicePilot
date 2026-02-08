import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { formatDate, formatDateTime } from './date';

describe('formatDate', () => {
    const originalLocale = Intl.DateTimeFormat().resolvedOptions().locale;

    afterEach(() => {
        // Restore original locale
        if (originalLocale) {
            // Note: We can't actually change the locale in tests, so we just verify behavior
        }
    });

    it('formats ISO date strings correctly', () => {
        const result = formatDate('2026-02-08');
        // Format should be Japanese: YYYY/MM/DD
        expect(result).toMatch(/\d{4}\/\d{1,2}\/\d{1,2}/);
    });

    it('formats date strings with time correctly', () => {
        const result = formatDate('2026-02-08T10:30:00');
        expect(result).toMatch(/\d{4}\/\d{1,2}\/\d{1,2}/);
    });

    it('handles different date formats', () => {
        expect(() => formatDate('2026-01-01')).not.toThrow();
        expect(() => formatDate('2026-12-31')).not.toThrow();
    });

    it('returns consistent format for the same date', () => {
        const date = '2026-02-08';
        const result1 = formatDate(date);
        const result2 = formatDate(date);
        expect(result1).toBe(result2);
    });

    it('handles edge cases', () => {
        // Leap year
        expect(() => formatDate('2024-02-29')).not.toThrow();

        // End of year
        expect(() => formatDate('2025-12-31')).not.toThrow();

        // Start of year
        expect(() => formatDate('2026-01-01')).not.toThrow();
    });
});

describe('formatDateTime', () => {
    it('formats ISO datetime strings correctly', () => {
        const result = formatDateTime('2026-02-08T10:30:00');
        // Should include both date and time
        expect(result).toMatch(/\d{4}\/\d{1,2}\/\d{1,2}/); // Date part
        expect(result).toMatch(/\d{1,2}:\d{2}/); // Time part
    });

    it('includes time information', () => {
        const result = formatDateTime('2026-02-08T14:45:30');
        // Should contain colon (time separator)
        expect(result).toContain(':');
    });

    it('handles midnight correctly', () => {
        const result = formatDateTime('2026-02-08T00:00:00');
        expect(result).toMatch(/\d{4}\/\d{1,2}\/\d{1,2}/);
        expect(result).toContain(':');
    });

    it('handles noon correctly', () => {
        const result = formatDateTime('2026-02-08T12:00:00');
        expect(result).toMatch(/\d{4}\/\d{1,2}\/\d{1,2}/);
        expect(result).toContain(':');
    });

    it('returns consistent format for the same datetime', () => {
        const datetime = '2026-02-08T15:30:45';
        const result1 = formatDateTime(datetime);
        const result2 = formatDateTime(datetime);
        expect(result1).toBe(result2);
    });

    it('handles dates without time component', () => {
        const result = formatDateTime('2026-02-08');
        // Should still format successfully
        expect(result).toMatch(/\d{4}\/\d{1,2}\/\d{1,2}/);
    });

    it('handles invalid date strings', () => {
        expect(() => formatDateTime('invalid-date')).toThrow(RangeError);
        expect(() => formatDateTime('invalid-date')).toThrow('Invalid date string');
        expect(() => formatDateTime('')).toThrow(RangeError);
    });
});

describe('formatDate - edge cases', () => {
    it('handles invalid date strings', () => {
        expect(() => formatDate('not-a-date')).toThrow(RangeError);
        expect(() => formatDate('not-a-date')).toThrow('Invalid date string');
        expect(() => formatDate('')).toThrow(RangeError);
    });

    it('formats dates with explicit format options', () => {
        // Test that the explicit format options produce consistent results
        const date1 = formatDate('2026-01-05');
        const date2 = formatDate('2026-01-05');
        expect(date1).toBe(date2);

        // Verify the format includes proper padding
        expect(date1).toMatch(/^\d{4}\/\d{2}\/\d{2}$/);
    });
});
