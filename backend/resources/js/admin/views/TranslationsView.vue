<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useAdmin } from '../useAdmin';
import Pager from '../components/Pager.vue';

/**
 * Translation workbench: coverage per content type × language, then a side-by-side editor
 * (default-language source ↔ target language) for the records still missing text.
 */
const { api, t, locale, number, errorText, languages } = useAdmin();

const summary = ref(null);
const type = ref('products');
const target = ref(null);
const missingOnly = ref(true);
const items = ref([]);
const meta = ref(null);
const page = ref(1);
const error = ref(null);
const drafts = reactive({});
const rowState = reactive({});

const types = computed(() => Object.keys(summary.value?.types ?? {}));
const attributes = computed(() => summary.value?.types?.[type.value]?.attributes ?? []);
const targets = computed(() => (summary.value?.locales ?? []).filter((c) => c !== summary.value?.default_locale));

// Inactive languages are listed too: content is translated before a language goes live.
function languageName(code) {
    const lang = summary.value?.languages?.find((l) => l.code === code) ?? languages.value.find((l) => l.code === code);
    if (!lang) return code;
    return lang.is_active === false ? `${lang.native_name} (${t('admin.translations.inactive')})` : lang.native_name;
}

function percent(typeName, code) {
    const c = summary.value.coverage[typeName];
    return c.total ? c.translated[code] / c.total : 1;
}

async function loadSummary() {
    try {
        summary.value = (await api.get('/admin/translations')).data;
        target.value ??= targets.value[0] ?? summary.value.default_locale;
    } catch (e) {
        error.value = e;
    }
}

async function loadItems() {
    if (!target.value) return;
    error.value = null;
    try {
        const res = await api.get(`/admin/translations/${type.value}`, { locale: target.value, missing: missingOnly.value, page: page.value, per_page: 20 });
        for (const item of res.data) drafts[item.id] = { ...(item.translation ?? {}) };
        items.value = res.data;
        meta.value = res.meta?.pagination ?? null;
    } catch (e) {
        error.value = e;
    }
}

async function save(item) {
    rowState[item.id] = { saving: true };
    try {
        const values = Object.fromEntries(attributes.value.map((a) => [a, drafts[item.id][a] || null]));
        await api.put(`/admin/translations/${type.value}/${item.id}`, { locale: target.value, values });
        rowState[item.id] = { saved: true };
        loadSummary();
    } catch (e) {
        rowState[item.id] = { error: e };
    }
}

watch([type, target, missingOnly], () => {
    page.value = 1;
    loadItems();
});
watch(page, loadItems);
// The context hints (e.g. product name of an option) are translated by the API.
watch(locale, loadItems);
onMounted(async () => {
    await loadSummary();
    loadItems();
});
</script>

<template>
    <section>
        <h2 class="mb-4 text-2xl font-semibold">{{ t('admin.nav.translations') }}</h2>
        <p v-if="error" class="mb-3 rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(error) }}</p>

        <div v-if="summary" class="mb-4 overflow-x-auto rounded-xl bg-white shadow-sm">
            <table class="w-full min-w-[560px]" data-test="coverage">
                <thead class="bg-stone-50 text-sm text-stone-600">
                    <tr>
                        <th class="px-3 py-2 text-start">{{ t('admin.translations.content') }}</th>
                        <th v-for="code in summary.locales" :key="code" class="px-3 py-2 text-start">{{ languageName(code) }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="name in types" :key="name" class="cursor-pointer border-t hover:bg-emerald-50" @click="type = name">
                        <td class="px-3 py-2" :class="{ 'font-semibold': type === name }">
                            {{ t(`admin.translations.types.${name}`) }}
                            <span class="text-xs text-stone-500">({{ number(summary.coverage[name].total) }})</span>
                        </td>
                        <td v-for="code in summary.locales" :key="code" class="px-3 py-2">
                            <span :class="percent(name, code) < 1 ? 'text-amber-700' : 'text-emerald-700'" :data-test="`coverage-${name}-${code}`">
                                {{ number(percent(name, code), { style: 'percent' }) }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-3 flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2">
                <span class="text-sm">{{ t('admin.translations.content') }}</span>
                <select v-model="type" class="rounded-lg border bg-white px-3 py-2">
                    <option v-for="name in types" :key="name" :value="name">{{ t(`admin.translations.types.${name}`) }}</option>
                </select>
            </label>
            <label class="flex items-center gap-2">
                <span class="text-sm">{{ t('admin.translations.target') }}</span>
                <select v-model="target" class="rounded-lg border bg-white px-3 py-2" data-test="target">
                    <option v-for="code in summary?.locales ?? []" :key="code" :value="code">{{ languageName(code) }}</option>
                </select>
            </label>
            <label class="flex items-center gap-2">
                <input v-model="missingOnly" type="checkbox" />
                <span>{{ t('admin.translations.missing_only') }}</span>
            </label>
        </div>

        <p v-if="!items.length" class="rounded-xl bg-white p-6 text-center text-stone-500">{{ t('admin.translations.all_done') }}</p>
        <div v-for="item in items" :key="item.id" class="mb-3 rounded-xl bg-white p-4 shadow-sm" data-test="translation-item">
            <p class="mb-2 text-xs text-stone-500">{{ item.context }}</p>
            <div v-for="attribute in attributes" :key="attribute" class="mb-2 grid gap-2 md:grid-cols-2">
                <div>
                    <p class="text-xs text-stone-500">{{ t(`admin.fields.${attribute}`) }} · {{ languageName(summary.default_locale) }}</p>
                    <p class="whitespace-pre-line rounded bg-stone-50 px-3 py-2">{{ item.source?.[attribute] || '—' }}</p>
                </div>
                <label>
                    <span class="text-xs text-stone-500">{{ t(`admin.fields.${attribute}`) }} · {{ languageName(target) }}</span>
                    <textarea
                        v-model="drafts[item.id][attribute]"
                        :rows="attribute === 'name' || attribute === 'title' ? 1 : 3"
                        class="w-full rounded border px-3 py-2"
                        :data-test="`draft-${attribute}`"
                    />
                </label>
            </div>
            <div class="flex items-center gap-3">
                <button class="rounded-lg bg-emerald-700 px-4 py-2 text-white disabled:opacity-50" :disabled="rowState[item.id]?.saving" data-test="save-translation" @click="save(item)">
                    {{ t('admin.save') }}
                </button>
                <span v-if="rowState[item.id]?.saved" class="text-emerald-700" role="status">✓ {{ t('admin.saved') }}</span>
                <span v-if="rowState[item.id]?.error" class="text-red-700" role="alert">{{ errorText(rowState[item.id].error) }}</span>
            </div>
        </div>
        <Pager :meta="meta" @page="page = $event" />
    </section>
</template>
