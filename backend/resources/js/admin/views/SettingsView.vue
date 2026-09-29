<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useAdmin, useRelationOptions, DEFAULT_CURRENCY } from '../useAdmin';
import { toMajor, toMinor } from '../../shared/format';

/**
 * Operational settings with overrides per scope. Each row shows the value in effect and
 * where it comes from; clearing a value falls back to the wider scope.
 */
const { api, t, user, money, errorText, can } = useAdmin();
const { load } = useRelationOptions(api);

const platform = computed(() => !user.value?.franchise_id && !user.value?.store_id);
const scopes = computed(() => [
    ...(platform.value ? ['GLOBAL'] : []),
    ...(can('franchises.view') ? ['FRANCHISE'] : []),
    'STORE',
]);
const scopeType = ref(null);
const scopeId = ref(null);
const choices = ref([]);
const settings = ref([]);
const drafts = ref({});
const error = ref(null);
const saved = ref(false);

async function loadChoices() {
    scopeId.value = null;
    choices.value = [];
    if (scopeType.value === 'FRANCHISE') choices.value = await load('franchises').catch(() => []);
    if (scopeType.value === 'STORE') choices.value = await load('stores').catch(() => []);
    scopeId.value = choices.value[0]?.value ?? null;
}

async function loadSettings() {
    if (scopeType.value !== 'GLOBAL' && !scopeId.value) return;
    error.value = null;
    saved.value = false;
    try {
        const { data } = await api.get('/admin/settings', { scope_type: scopeType.value, scope_id: scopeId.value });
        settings.value = data.settings;
        drafts.value = Object.fromEntries(data.settings.map((s) => [s.key, display(s, s.value)]));
    } catch (e) {
        error.value = e;
    }
}

function display(setting, value) {
    if (value === null || value === undefined) return '';
    return setting.type === 'money' ? toMajor(value, DEFAULT_CURRENCY) : value;
}

function effective(setting) {
    return setting.type === 'money' ? money(setting.effective) : setting.effective;
}

async function save() {
    error.value = null;
    saved.value = false;
    const values = Object.fromEntries(settings.value.map((s) => {
        const v = drafts.value[s.key];
        if (v === '' || v === null || v === undefined) return [s.key, null];
        return [s.key, s.type === 'money' ? toMinor(v, DEFAULT_CURRENCY) : Number(v)];
    }));
    try {
        const { data } = await api.put('/admin/settings', { scope_type: scopeType.value, scope_id: scopeId.value, values });
        settings.value = data.settings;
        saved.value = true;
    } catch (e) {
        error.value = e;
    }
}

watch(scopeType, async () => {
    await loadChoices();
    loadSettings();
});
watch(scopeId, loadSettings);
onMounted(() => {
    scopeType.value = scopes.value[0];
});
</script>

<template>
    <section>
        <h2 class="mb-4 text-2xl font-semibold">{{ t('admin.nav.settings') }}</h2>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm">
            <label class="flex flex-col text-sm">
                {{ t('admin.settings.scope') }}
                <select v-model="scopeType" class="rounded border bg-white px-2 py-1" data-test="scope-type">
                    <option v-for="s in scopes" :key="s" :value="s">{{ t(`admin.settings.scopes.${s}`) }}</option>
                </select>
            </label>
            <label v-if="choices.length" class="flex flex-col text-sm">
                {{ t(`admin.settings.scopes.${scopeType}`) }}
                <select v-model="scopeId" class="rounded border bg-white px-2 py-1">
                    <option v-for="c in choices" :key="c.value" :value="c.value">{{ c.label }}</option>
                </select>
            </label>
        </div>

        <p v-if="error" class="mb-3 rounded-lg bg-red-50 p-3 text-red-800" role="alert">{{ errorText(error) }}</p>

        <form class="rounded-xl bg-white p-4 shadow-sm" @submit.prevent="save">
            <div v-for="s in settings" :key="s.key" class="mb-4 grid gap-2 border-b pb-4 md:grid-cols-2" data-test="setting">
                <div>
                    <label :for="`setting-${s.key}`" class="font-medium">{{ t(`admin.settings.keys.${s.key}.label`) }}</label>
                    <p class="text-sm text-stone-500">{{ t(`admin.settings.keys.${s.key}.help`) }}</p>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <input
                            :id="`setting-${s.key}`"
                            v-model="drafts[s.key]"
                            type="number"
                            min="0"
                            class="w-40 rounded border px-3 py-2"
                            :placeholder="String(display(s, s.effective))"
                        />
                        <span v-if="s.type === 'money'" class="text-sm text-stone-500">{{ DEFAULT_CURRENCY }}</span>
                    </div>
                    <p class="mt-1 text-sm text-stone-600">
                        {{ t('admin.settings.in_effect', { value: effective(s), source: t(`admin.settings.scopes.${s.source}`) }) }}
                    </p>
                </div>
            </div>
            <p class="mb-3 text-sm text-stone-500">{{ t('admin.settings.clear_hint') }}</p>
            <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2 font-semibold text-white" data-test="save-settings">{{ t('admin.save') }}</button>
            <span v-if="saved" class="ms-3 text-emerald-700" role="status">✓ {{ t('admin.saved') }}</span>
        </form>
    </section>
</template>
