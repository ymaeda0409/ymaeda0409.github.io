import { createI18n } from 'vue-i18n';
import en from '../locales/en.json';
import ny from '../locales/ny.json';
import ja from '../locales/ja.json';

/**
 * Bundled UI messages (switching language needs no network). Adding a language =
 * adding resources/js/locales/<code>.json (and locales/admin/<code>.json) and registering it here.
 */
export const messages = { en, ny, ja };
export const FALLBACK = 'en';
const LOCALE_KEY = 'bento.locale';

export function isSupported(code) {
    return Object.prototype.hasOwnProperty.call(messages, code);
}

/** Saved choice → browser languages → English. */
export function initialLocale() {
    const saved = localStorage.getItem(LOCALE_KEY);
    if (saved && isSupported(saved)) return saved;
    for (const tag of navigator.languages ?? []) {
        const code = tag.split('-')[0];
        if (isSupported(code)) return code;
    }
    return FALLBACK;
}

export function rememberLocale(code) {
    localStorage.setItem(LOCALE_KEY, code);
}

/**
 * Locale for Intl number/date formatting: the UI locale when the browser knows it,
 * otherwise English (generic rule — e.g. Chichewa lacks CLDR data in some browsers).
 */
export function formattingLocale(code) {
    try {
        return Intl.DateTimeFormat.supportedLocalesOf([code]).length ? code : FALLBACK;
    } catch {
        return FALLBACK;
    }
}

/** Recursively merges message objects; values in `extra` win. */
export function mergeMessages(base, extra) {
    const out = { ...base };
    for (const [key, value] of Object.entries(extra ?? {})) {
        out[key] = value && typeof value === 'object' && !Array.isArray(value) && typeof base?.[key] === 'object'
            ? mergeMessages(base[key], value)
            : value;
    }
    return out;
}

/** `bundle` lets an app add its own messages on top of the shared ones. */
export function makeI18n(locale = initialLocale(), bundle = messages) {
    return createI18n({ legacy: false, locale, fallbackLocale: FALLBACK, messages: bundle });
}

/** API error code → translation key, generic fallback for unknown codes. */
export function errorKey(te, code) {
    const key = `errors.${code}`;
    return te(key) ? key : 'errors.UNKNOWN';
}

/** Order status code → translation key (status labels are never hard-coded). */
export function statusKey(te, status) {
    const key = `status.${status}`;
    return te(key) ? key : 'status.UNKNOWN';
}
