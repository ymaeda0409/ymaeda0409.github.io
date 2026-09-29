import { inject, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { errorKey } from '../shared/i18n';
import { formatDateTime, formatMoney, formatNumber } from '../shared/format';
import { RESOURCES, translated } from './resources';

export const DEFAULT_CURRENCY = 'MWK';

/**
 * Everything a view needs: API client, user, permissions, navigation and
 * locale-aware formatters (money is always integer minor units).
 */
export function useAdmin() {
    const ctx = inject('admin');
    const i18n = useI18n();
    const { t, te, locale } = i18n;

    return {
        ...ctx,
        t,
        te,
        locale,
        money: (minor, currency = DEFAULT_CURRENCY) => formatMoney(minor, currency, locale.value),
        number: (value, options) => formatNumber(value, locale.value, options),
        dateTime: (iso) => formatDateTime(iso, locale.value),
        errorText: (e) => t(errorKey(te, e?.code ?? 'UNKNOWN')),
        enumText: (group, value) => (value === null || value === undefined ? '' : te(`admin.enums.${group}.${value}`) ? t(`admin.enums.${group}.${value}`) : value),
        text: (translations, attribute = 'name') => translated(translations, attribute, locale.value),
    };
}

/**
 * A loading / error / data triple for one request.
 */
export function useLoader(fn) {
    const state = reactive({ loading: false, error: null, data: null });
    async function run(...args) {
        state.loading = true;
        state.error = null;
        try {
            state.data = await fn(...args);
        } catch (e) {
            state.error = e;
        } finally {
            state.loading = false;
        }
        return state.data;
    }
    return { state, run };
}

const optionCache = new Map();

/**
 * Choices for a relation field (e.g. franchise_id), labelled in the UI language.
 */
export function useRelationOptions(api) {
    const { locale } = useI18n();

    function label(row) {
        return row.name || translated(row.translations, 'name', locale.value) || row.code || row.sku || `#${row.id}`;
    }

    async function load(source) {
        if (!optionCache.has(source)) {
            optionCache.set(source, api.get(RESOURCES[source].endpoint, { per_page: 100 })
                .then(({ data }) => data)
                .catch((e) => {
                    optionCache.delete(source);
                    throw e;
                }));
        }
        return (await optionCache.get(source)).map((row) => ({ value: row.id, label: label(row), row }));
    }

    return { load, label };
}

export function clearRelationCache(source = null) {
    if (source) optionCache.delete(source);
    else optionCache.clear();
}

/** Triggers a browser download for a Blob. */
export function saveBlob({ blob, filename }) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

export function usePagination() {
    const page = ref(1);
    const meta = ref(null);
    return { page, meta };
}
