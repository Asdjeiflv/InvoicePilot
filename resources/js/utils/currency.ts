/**
 * Currency formatting utilities for Japanese Yen (JPY)
 */

/**
 * Singleton instance of the currency formatter for performance optimization
 * Creating a new Intl.NumberFormat instance is expensive, so we reuse one instance
 */
const currencyFormatter = new Intl.NumberFormat('ja-JP', {
    style: 'currency',
    currency: 'JPY',
    maximumFractionDigits: 0,
    minimumFractionDigits: 0,
});

/**
 * Format a number as Japanese Yen currency
 *
 * @param amount - The amount to format (will be rounded to nearest integer)
 * @returns Formatted currency string (e.g., "¥1,000,000")
 * @throws {RangeError} If amount is NaN or Infinity
 *
 * @example
 * formatCurrency(1000000) // "￥1,000,000"
 * formatCurrency(2200000) // "￥2,200,000"
 * formatCurrency(1234.56) // "￥1,235" (rounded)
 */
export const formatCurrency = (amount: number): string => {
    if (!Number.isFinite(amount)) {
        throw new RangeError('Amount must be a finite number');
    }
    return currencyFormatter.format(amount);
};
