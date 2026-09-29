import { formattingLocale } from './i18n';

/**
 * Money is integer minor units everywhere; `exponent` is the number of minor digits.
 * Formatting follows the UI language when the browser supports it, else English.
 */
export const CURRENCY_EXPONENT = { MWK: 2 };

export function exponentOf(currency) {
    return CURRENCY_EXPONENT[currency] ?? 2;
}

export function toMajor(minor, currency) {
    return minor / 10 ** exponentOf(currency);
}

export function toMinor(major, currency) {
    return Math.round(Number(major) * 10 ** exponentOf(currency));
}

export function formatMoney(minor, currency, locale) {
    if (minor === null || minor === undefined) return '';
    return new Intl.NumberFormat(formattingLocale(locale), {
        style: 'currency',
        currency,
        currencyDisplay: 'code',
        maximumFractionDigits: 0,
    }).format(toMajor(minor, currency));
}

export function formatNumber(value, locale, options = {}) {
    if (value === null || value === undefined) return '';
    return new Intl.NumberFormat(formattingLocale(locale), options).format(value);
}

export function formatDateTime(iso, locale, options = { dateStyle: 'medium', timeStyle: 'short' }) {
    if (!iso) return '';
    return new Intl.DateTimeFormat(formattingLocale(locale), options).format(new Date(iso));
}

/** "2026-03-10" (a calendar date, no timezone shift) → localized short date. */
export function formatDay(day, locale) {
    if (!day) return '';
    const [y, m, d] = day.split('-').map(Number);
    return new Intl.DateTimeFormat(formattingLocale(locale), { month: 'short', day: 'numeric', timeZone: 'UTC' })
        .format(new Date(Date.UTC(y, m - 1, d)));
}

/** Today in the given IANA timezone as YYYY-MM-DD. */
export function todayIn(timezone, now = new Date()) {
    return new Intl.DateTimeFormat('en-CA', { timeZone: timezone, year: 'numeric', month: '2-digit', day: '2-digit' }).format(now);
}

export function addDays(day, n) {
    const [y, m, d] = day.split('-').map(Number);
    const date = new Date(Date.UTC(y, m - 1, d + n));
    return date.toISOString().slice(0, 10);
}
