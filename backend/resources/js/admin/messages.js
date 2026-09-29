import { mergeMessages, messages } from '../shared/i18n';
import en from '../locales/admin/en.json';
import ny from '../locales/admin/ny.json';
import ja from '../locales/admin/ja.json';

/** Admin-only texts (locales/admin/<code>.json) layered over the shared back-office texts. */
export const adminOnly = { en, ny, ja };

export const adminMessages = Object.fromEntries(
    Object.entries(messages).map(([code, base]) => [code, mergeMessages(base, adminOnly[code] ?? {})]),
);
