import { describe, it, expect } from 'vitest';
import { formatCurrency } from './currency';

describe('formatCurrency', () => {
    it('formats positive numbers correctly', () => {
        expect(formatCurrency(1000)).toBe('￥1,000');
        expect(formatCurrency(1000000)).toBe('￥1,000,000');
        expect(formatCurrency(2200000)).toBe('￥2,200,000');
    });

    it('formats zero correctly', () => {
        expect(formatCurrency(0)).toBe('￥0');
    });

    it('formats negative numbers correctly', () => {
        expect(formatCurrency(-1000)).toBe('-￥1,000');
        expect(formatCurrency(-2200000)).toBe('-￥2,200,000');
    });

    it('removes decimal places', () => {
        expect(formatCurrency(1000.99)).toBe('￥1,001');
        expect(formatCurrency(2200000.50)).toBe('￥2,200,001');
        expect(formatCurrency(999.49)).toBe('￥999');
    });

    it('handles very large numbers', () => {
        expect(formatCurrency(9999999999)).toBe('￥9,999,999,999');
    });

    it('handles very small numbers', () => {
        expect(formatCurrency(1)).toBe('￥1');
        expect(formatCurrency(0.1)).toBe('￥0');
        expect(formatCurrency(0.9)).toBe('￥1');
    });

    it('uses Japanese Yen format', () => {
        const result = formatCurrency(1000);
        expect(result).toContain('￥');
        expect(result).toContain(',');
    });

    it('does not show decimal places', () => {
        const result = formatCurrency(1234.56);
        expect(result).not.toContain('.');
        expect(result).toBe('￥1,235');
    });

    it('handles invalid inputs gracefully', () => {
        expect(() => formatCurrency(NaN)).toThrow(RangeError);
        expect(() => formatCurrency(NaN)).toThrow('Amount must be a finite number');

        expect(() => formatCurrency(Infinity)).toThrow(RangeError);
        expect(() => formatCurrency(-Infinity)).toThrow(RangeError);
    });

    it('is type-safe with TypeScript', () => {
        // This test ensures that TypeScript enforces number type
        const amount: number = 1000;
        expect(formatCurrency(amount)).toBe('￥1,000');
    });
});
